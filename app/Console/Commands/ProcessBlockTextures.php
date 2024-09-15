<?php

namespace App\Console\Commands;

use App\Models\Block;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ColorInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Spatie\Color\Hex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

class ProcessBlockTextures extends Command
{
    const MINECRAFT_VERSION = '1.21.1';

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

        $this->downloadJarAndExtractTextures();
        $this->reduceTexturesToOneColor();
        $this->createBlockItemsFromReducedTextures();
    }

    private function downloadJarAndExtractTextures(): void
    {
        $body = cache()->remember('mcversions-download:' . self::MINECRAFT_VERSION, '10 minutes', function () {
            return Http::get(sprintf('https://mcversions.net/download/%s', self::MINECRAFT_VERSION))->body();
        });

        $pattern = '/href="([^"]*client\.jar)"/';

        if (!preg_match($pattern, $body, $matches)) {
            $this->fail('Could not find client.jar download link');
        }

        $path = storage_path(sprintf('app/client-%s.jar', self::MINECRAFT_VERSION));

        if (!File::exists($path)) {
            $this->line('Downloading jar file');
            Http::withOptions(['sink' => $path])
                ->get($matches[1]);
        }

        $this->line('Extracting textures from jar file');
        $zip = new ZipArchive();
        $zip->open($path);
        $zip->extractTo(storage_path('app/minecraft-' . self::MINECRAFT_VERSION));
        $zip->close();

        File::moveDirectory(storage_path('app/minecraft-' . self::MINECRAFT_VERSION . '/assets/minecraft/textures/block'), public_path('images/blocks'));
        File::deleteDirectory(storage_path('app/minecraft-' . self::MINECRAFT_VERSION));
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
         * @var SplFileInfo $file
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

            File::makeDirectory(public_path('images/blocks/reduced'), 0755, true, true);
            $image
                ->reduceColors(1)
                ->save(public_path('images/blocks/reduced/') . $file->getFileName());
        });
        $this->output->newLine();
    }

    private function createBlockItemsFromReducedTextures(): void
    {
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth('== 0')
            ->in(public_path('images/blocks/reduced'))
            ->name('*.png');

        $this->info(sprintf('Creating %s block items', $files->count()));

        /**
         * @var SplFileInfo $file
         */
        $this->withProgressBar($files, function ($file) {
            $color = $this->averageColor($this->manager->read($file->getRealPath()));

            $hex = Hex::fromString('#' . $color->toHex());
            $lab = $hex->toCIELab();
            Block::create([
                'name' => str($file->getBasename('.png'))->replace('_', ' ')->title(),
                'image' => $file->getFileName(),
                'hex' => $hex,
                'lab' => $lab,
            ]);
        });
    }

    private function averageColor(ImageInterface $image): ColorInterface
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
