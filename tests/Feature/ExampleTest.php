<?php

use App\Models\Block;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders gradients from scalar cache entries when object unserialization is disabled', function () {
    config([
        'cache.default' => 'file',
        'cache.serializable_classes' => false,
        'cache.stores.file.path' => storage_path('framework/cache/testing'),
    ]);

    Block::query()->insert([
        [
            'name' => 'Black Wool',
            'image' => 'black_wool.png',
            'hex' => '#000000',
            'lab' => 'CIELab(0, 0, 0)',
        ],
        [
            'name' => 'Gray Wool',
            'image' => 'gray_wool.png',
            'hex' => '#777777',
            'lab' => 'CIELab(50, 0, 0)',
        ],
        [
            'name' => 'White Wool',
            'image' => 'white_wool.png',
            'hex' => '#ffffff',
            'lab' => 'CIELab(100, 0, 0)',
        ],
    ]);

    $query = '/?startBlockId=1&endBlockId=3&steps=3';

    $this->get($query)->assertOk();

    app()->forgetInstance('blocks');

    $this->get($query)
        ->assertOk()
        ->assertSee('Gray Wool');
});
