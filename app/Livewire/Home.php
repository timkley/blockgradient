<?php

namespace App\Livewire;

use App\Models\Block;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Color\CIELab;
use Spatie\Color\Distance;

class Home extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public ?int $startBlockId = null;

    #[Url]
    public ?int $endBlockId = null;

    public function render()
    {
        $startBlock = Block::find($this->startBlockId);
        $endBlock = Block::find($this->endBlockId);

        return view('livewire.home', [
            'blocks' => Block::where('name', 'like', "%{$this->search}%")->get(),
            'startBlock' => $startBlock,
            'endBlock' => $endBlock,
            'gradient' => $this->generateGradient($startBlock, $endBlock),
        ]);
    }

    public function setStartBlock($id): void
    {
        $this->startBlockId = $id;
    }

    public function setEndBlock($id): void
    {
        $this->endBlockId = $id;
    }

    private function generateGradient(?Block $startBlock, ?Block $endBlock, int $steps = 10): Collection
    {
        if (! $startBlock || ! $endBlock) {
            return collect();
        }

        $startColor = CIELab::fromString($startBlock->lab);
        $endColor = CIELab::fromString($endBlock->lab);

        $gradientSteps = $this->generateGradientSteps($startColor, $endColor, $steps);

        $gradientBlocks = collect();
        foreach ($gradientSteps as $step) {
            $gradientBlocks->push($this->findClosestBlock($gradientBlocks, $step));
        }

        return $gradientBlocks;
    }

    private function generateGradientSteps(CIELab $start, CIELab $end, int $steps): array
    {
        $colors = [];

        for ($i = 0; $i < $steps; $i++) {
            $t = $i / ($steps - 1);
            $l = $start->l() + $t * ($end->l() - $start->l());
            $a = $start->a() + $t * ($end->a() - $start->a());
            $b = $start->b() + $t * ($end->b() - $start->b());

            $colors[] = CIELab::fromString("CIELab($l, $a, $b)");
        }

        return $colors;
    }

    private function findClosestBlock(Collection $blocksToRemove, CIELab $color): Block
    {
        $blocks = cache()->rememberForever('1.21:closest-blocks-for-color-' . $color->toHex(), function () use ($blocksToRemove, $color) {
            return app('blocks')
                ->reject(function ($block) use ($blocksToRemove) {
                    return $blocksToRemove->contains('id', $block->id);
                })
                ->map(function ($block) use ($color) {
                    $block->distance = Distance::CIEDE2000($color, CIELab::fromString($block->lab));

                    return $block;
                })
                ->sortBy('distance')
                ->take(10);
        });

        return $blocks->first();
    }
}
