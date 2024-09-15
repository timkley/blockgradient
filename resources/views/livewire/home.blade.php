<div class="mx-auto max-w-screen-lg">
    <h1 class="text-4xl font-bold">Minecraft Block Gradient Generator</h1>
    <p class="my-4">Select a start and end block to generate a gradient of blocks between them.</p>

    @if ($startBlock || $endBlock)
        <div class="mb-6 flex items-center justify-between">
            <div class="aspect-square">
                @if ($startBlock)
                    <img
                        alt="{{ $startBlock->name }}"
                        class="size-44 [image-rendering:pixelated]"
                        loading="lazy"
                        src="{{ asset('images/blocks/'.$startBlock->image) }}"
                    />
                @endif
            </div>
            <div>
                <x-heroicon-s-arrow-right class="size-12 text-stone-500" />
            </div>
            <div class="aspect-square">
                @if ($endBlock)
                    <img
                        alt="{{ $endBlock->name }}"
                        class="size-44 [image-rendering:pixelated]"
                        loading="lazy"
                        src="{{ asset('images/blocks/'.$endBlock->image) }}"
                    />
                @endif
            </div>
        </div>
    @endif

    @if ($gradient)
        <div class="sticky top-0 bg-stone-300 py-6">
            <h2 class="text-3xl font-medium">Your gradient</h2>

            <div
                class="mt-3 grid [grid-template-columns:repeat(var(--steps),minmax(0,1fr))]"
                style="--steps: {{ count($gradient) }}"
            >
                @foreach ($gradient as $block)
                    <div
                        class="aspect-square"
                        style="background-color: {{ $block->hex }}"
                    >
                        <img
                            alt="{{ $block->name }}"
                            class="size-full [image-rendering:pixelated]"
                            loading="lazy"
                            src="{{ asset('images/blocks/'.$block->image) }}"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <h2 class="text-3xl font-medium">All Blocks</h2>

    <input
        wire:model.live="search"
        class="border-500 my-2 rounded-md px-4 py-2 shadow"
        placeholder="Search..."
    />

    <div class="grid grid-cols-3 gap-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10">
        @foreach ($blocks as $block)
            <div>
                <img
                    alt="{{ $block->name }}"
                    class="w-64 [image-rendering:pixelated]"
                    loading="lazy"
                    src="{{ asset('images/blocks/'.$block->image) }}"
                />
                <div class="flex">
                    <button
                        type="button"
                        class="aspect-square size-full rounded border-stone-300 bg-stone-200 hover:bg-stone-100"
                        wire:click="setStartBlock({{ $block->id }})"
                    >
                        S
                    </button>
                    <button
                        type="button"
                        class="aspect-square size-full rounded border-stone-300 bg-stone-200 hover:bg-stone-100"
                        wire:click="setEndBlock({{ $block->id }})"
                    >
                        E
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
