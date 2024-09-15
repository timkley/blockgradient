<?php

namespace App\Console\Commands;

use App\Models\Block;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Spatie\Color\Hex;
use Symfony\Component\Finder\Finder;

class ProcessBlockTextures extends Command
{
    protected $signature = 'app:process-block-textures';

    protected $description = 'Process block textures to calculate perceived LAB colors';

    private ImageManager $manager;

    public function __construct()
    {
        parent::__construct();

        $this->manager = new ImageManager(new Driver());
    }

    public function handle(): void
    {
        DB::table('blocks')->truncate();
        File::cleanDirectory(public_path('images/blocks/reduced'));

        $this->info('Processing block textures');

        $this->reduceTexturesToOneColor();
        $this->createBlockItemsFromReducedTextures();
    }

    private function reduceTexturesToOneColor(): void
    {
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth('== 0')
            ->in(public_path('images/blocks'))
            ->name('*.png');

        $this->info(sprintf('Reducing colors of %d block textures', $files->count()));

        /**
         * @var \Symfony\Component\Finder\SplFileInfo $file
         */
        $this->withProgressBar($files, function ($file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'png') {
                return;
            }

            $image = $this->manager->read($file->getRealPath());

            if ($image->width() !== 16 || $image->height() !== 16) {
                return;
            }

            if ($image->pickColor(0, 0)->isTransparent() || $image->pickColor(15, 15)->isTransparent()) {
                return;
            }

            $image
                ->reduceColors(1)
                ->save(public_path('images/blocks/reduced/').$file->getFileName());
        });
    }

    private function createBlockItemsFromReducedTextures()
    {
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth('== 0')
            ->in(public_path('images/blocks/reduced'))
            ->name('*.png');

        $this->info(sprintf('Creating %s block items', $files->count()));

        /**
         * @var \Symfony\Component\Finder\SplFileInfo $file
         */
        $this->withProgressBar($files, function ($file) {
            $color = $this->averageColor($this->manager->read($file->getRealPath()));

            $hex = Hex::fromString('#'.$color->toHex());
            $lab = $hex->toCIELab();
            Block::create([
                'name' => str($file->getBasename('.png'))->replace('_', ' ')->title(),
                'image' => $file->getFileName(),
                'hex' => $hex,
                'lab' => $lab,
            ]);
        });
    }

    private function averageColor(ImageInterface $image)
    {
        $width = $image->width();
        $height = $image->height();

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = $image->pickColor($x, $y);

                $hex = $color->toHex();

                if (! str_starts_with($hex, '000000') && ! str_starts_with($hex, 'ffffff')) {
                    return $color;
                }
            }
        }

        return $image->pickColor(0, 0);
    }
}
