<?php

namespace App\Livewire;

use App\Console\Commands\ProcessBlockTextures;
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
    public int $steps = 7;

    #[Url]
    public ?int $startBlockId = null;

    #[Url]
    public ?int $endBlockId = null;

    public function render()
    {
        $startBlock = Block::find($this->startBlockId);
        $endBlock = Block::find($this->endBlockId);

        return view('livewire.home', [
            'version' => ProcessBlockTextures::MINECRAFT_VERSION,
            'blocks' => Block::where('name', 'like', "%{$this->search}%")->get(),
            'startBlock' => $startBlock,
            'endBlock' => $endBlock,
            'gradient' => $this->generateGradient($startBlock, $endBlock, $this->steps),
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

    public function setSteps(int $steps): void
    {
        $this->steps = $steps;
    }

    private function generateGradient(?Block $startBlock, ?Block $endBlock, int $steps = 10): Collection
    {
        $gradient = collect([$startBlock, $endBlock]);
        if (! $startBlock || ! $endBlock) {
            return $gradient->filter();
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
        $blocks = cache()->rememberForever(ProcessBlockTextures::MINECRAFT_VERSION.':closest-blocks-for-color-'.$color->toHex(), function () use ($blocksToRemove, $color) {
            return app('blocks')
                ->reject(function ($block) use ($blocksToRemove) {
                    return $blocksToRemove->contains('id', $block->id);
                })
                ->map(function ($block) use ($color) {
                    $block->distance = Distance::CIE76($color, CIELab::fromString($block->lab));

                    return $block;
                })
                ->sortBy('distance')
                ->take(10);
        });

        /** @var Block $blocks */
        return $blocks->first();
    }
}
