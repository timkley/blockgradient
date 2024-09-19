<?php

namespace App\Console\Commands;

use App\Models\Block;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
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

    private array $excludedFileNameRegexes = [
        '/anvil/',
        '/beacon/',
        '/bedrock/',
        '/bottom/',
        '/brewing_stand_base/',
        '/campfire_log/',
        '/cauldron_(inner|top)/',
        '/comparator/',
        '/composter_top/',
        '/crafting_table_top/',
        '/daylight_detector/',
        '/debug/',
        '/dirt_path_top/',
        '/door/',
        '/dragon_egg/',
        '/enchanting_table/',
        '/farmland/',
        '/fletching_table_top/',
        '/flowering_azalea_top/',
        '/furnace_front_on/',
        '/item_frame/',
        '/hopper/',
        '/lectern/',
        '/lightning_rod/',
        '/loom_top/',
        '/piston_(inner|top)/',
        '/particle/',
        '/repeater/',
        '/scaffolding_top/',
        '/suspicious/',
        '/spawner/',
        '/vault/',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->manager = new ImageManager(new Driver());
    }

    public function handle(): void
    {
        DB::table('blocks')->truncate();

        $this->info('Processing block textures');

        $this->downloadJarAndExtractTextures();
        $this->createBlockModelsFromTextures();
    }

    private function downloadJarAndExtractTextures(): void
    {
        $body = cache()->remember('mcversions-download:'.self::MINECRAFT_VERSION, '10 minutes', function () {
            return Http::get(sprintf('https://mcversions.net/download/%s', self::MINECRAFT_VERSION))->body();
        });

        $pattern = '/href="([^"]*client\.jar)"/';

        if (! preg_match($pattern, $body, $matches)) {
            $this->fail('Could not find client.jar download link');
        }

        $path = storage_path(sprintf('app/client-%s.jar', self::MINECRAFT_VERSION));

        if (! File::exists($path)) {
            $this->line('Downloading jar file');
            Http::withOptions(['sink' => $path])
                ->get($matches[1]);
        }

        $this->line('Extracting textures from jar file');

        $extractPath = storage_path('app/minecraft-'.self::MINECRAFT_VERSION);
        $zip = new ZipArchive();
        $zip->open($path);
        $zip->extractTo($extractPath);
        $zip->close();

        File::makeDirectory(public_path('images/blocks'), 0755, true, true);
        File::moveDirectory(storage_path('app/minecraft-'.self::MINECRAFT_VERSION.'/assets/minecraft/textures/block'), public_path('images/blocks'));
        File::deleteDirectory(storage_path('app/minecraft-'.self::MINECRAFT_VERSION));
    }

    private function createBlockModelsFromTextures(): void
    {
        $finder = new Finder();
        $files = $finder
            ->files()
            ->depth('== 0')
            ->in(public_path('images/blocks'))
            ->name('*.png');

        $this->info(sprintf('Creating %s block items', $files->count()));

        /**
         * @var SplFileInfo $file
         */
        $this->withProgressBar($files, function ($file) {
            $image = $this->manager->read($file->getRealPath());

            if ($image->width() !== 16 || $image->height() !== 16) {
                File::delete($file->getRealPath());
                return;
            }
            if ($image->pickColor(0, 0)->isTransparent() || $image->pickColor(15, 15)->isTransparent()) {
                File::delete($file->getRealPath());
                return;
            }

            if (collect($this->excludedFileNameRegexes)->contains(fn ($regex) => preg_match($regex, $file->getFilename()))) {
                $this->newLine();
                $this->line('Skipping '.$file->getFilename());
                return;
            }


            $palette = Palette::fromFilename($file->getRealPath(), Color::fromHexToInt('#FFFFFF'));
            $extractor = new ColorExtractor($palette);
            $color = Color::fromIntToHex($extractor->extract()[0]);

            $hex = Hex::fromString($color);
            $lab = $hex->toCIELab();
            Block::create([
                'name' => str($file->getBasename('.png'))->replace('_', ' ')->title(),
                'image' => $file->getFileName(),
                'hex' => $hex,
                'lab' => $lab,
            ]);
        });
    }
}
