<div class="mx-auto max-w-(--breakpoint-lg)">
    <h1 class="text-2xl font-bold lg:text-3xl">
        Minecraft Block Gradient Generator
        <span class="rounded-lg bg-stone-400 px-1.5 py-0.5 text-sm font-normal text-stone-900">v{{ $version }}</span>
    </h1>
    <p class="my-4">Select a start and end block to generate a gradient of blocks between them.</p>

    <div
        class="top-0 bg-stone-300 py-6"
        wire:loading.class="opacity-50"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            <h2 class="text-3xl font-medium">Your gradient</h2>

            <div class="flex items-center gap-x-2">
                @foreach ([3, 5, 7, 9] as $step)
                    <button
                        type="button"
                        class="rounded-sm text-xs px-2 py-1 {{ $steps === $step ? 'bg-stone-200' : 'bg-stone-300' }} hover:bg-stone-100"
                        wire:click="setSteps({{ $step }})"
                    >
                        {{ $step }} steps
                    </button>
                @endforeach
            </div>
        </div>

        <div
            @class([
                'mt-3 grid-cols-5 lg:grid-cols-[repeat(var(--steps),minmax(0,1fr))]',
                'grid' => $startBlock && $endBlock,
                'flex items-center justify-between' => ! $startBlock || ! $endBlock,
            ])
            style="--steps: {{ $steps }}"
        >
            @if (! $startBlock)
                <div class="aspect-square max-w-40 bg-stone-400 p-3 text-center font-mono text-sm uppercase">select a start block</div>
            @endif

            @foreach ($gradient as $block)
                <div class="relative aspect-square max-w-40 flex-1">
                    <img
                        alt="{{ $block->name }}"
                        class="size-full [image-rendering:pixelated]"
                        loading="lazy"
                        src="{{ asset('images/blocks/'.$block->image) }}"
                    />

                    @env('local')
                        <div
                            class="absolute bottom-0 left-0 grid aspect-square place-content-center"
                            style="background-color: {{ $block->hex }}"
                        >
                            <span class="-rotate-45 rounded-sm bg-white/20 px-1 font-mono uppercase"> debug </span>
                        </div>
                    @endenv
                </div>
            @endforeach

            @if (! $endBlock)
                <div class="aspect-square max-w-40 bg-stone-400 p-3 text-center font-mono text-sm uppercase">select an end block</div>
            @endif
        </div>
    </div>

    <h2 class="text-3xl font-medium">All Blocks</h2>

    <input
        wire:model.live="search"
        class="bg-white my-2 rounded-md px-4 py-2 shadow-sm"
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
                        class="aspect-square size-full rounded-sm border-stone-300 bg-stone-200 hover:bg-stone-100"
                        wire:click="setStartBlock({{ $block->id }})"
                    >
                        S
                    </button>
                    <button
                        type="button"
                        class="aspect-square size-full rounded-sm border-stone-300 bg-stone-200 hover:bg-stone-100"
                        wire:click="setEndBlock({{ $block->id }})"
                    >
                        E
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
