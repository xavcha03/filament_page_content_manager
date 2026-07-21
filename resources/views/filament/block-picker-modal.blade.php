@php
    /** @var array<string, list<array{type: string, label: string, icon: mixed, description: string, previewUrl: string|null, searchText: string}>> $groups */
    use Xavcha\PageContentManager\Filament\Support\BlockPickerStyles;
@endphp

@if ($styles = BlockPickerStyles::inline())
    <style>{!! $styles !!}</style>
@endif

<div
    x-data="{
        query: '',
        allSearchTexts: @js(collect($groups)->flatten(1)->pluck('searchText')->values()->all()),
        matches(searchText) {
            const q = this.query.trim().toLowerCase()

            if (! q) {
                return true
            }

            return searchText.includes(q)
        },
        groupHasMatches(searchTexts) {
            return searchTexts.some((text) => this.matches(text))
        },
        get hasAnyMatch() {
            return this.allSearchTexts.some((text) => this.matches(text))
        },
    }"
    class="pcm-block-picker"
>
    <div class="pcm-block-picker__search">
        <x-filament::input.wrapper>
            <x-filament::input
                type="search"
                x-model.debounce.150ms="query"
                placeholder="Rechercher un bloc…"
                autocomplete="off"
            />
        </x-filament::input.wrapper>
    </div>

    <div class="pcm-block-picker__scroll">
        @forelse ($groups as $groupLabel => $blocks)
            @php
                $searchTexts = array_column($blocks, 'searchText');
            @endphp
            <section
                x-show="groupHasMatches(@js($searchTexts))"
                class="pcm-block-picker__group"
            >
                <div class="pcm-block-picker__group-header">
                    <h3 class="pcm-block-picker__group-title">
                        {{ $groupLabel }}
                    </h3>

                    <span class="pcm-block-picker__group-count">
                        {{ count($blocks) }}
                    </span>
                </div>

                <div class="pcm-block-picker__grid">
                    @foreach ($blocks as $block)
                        <button
                            type="button"
                            x-show="matches(@js($block['searchText']))"
                            wire:click="callMountedAction({ block: @js($block['type']) })"
                            class="pcm-block-picker__card"
                        >
                            @if (filled($block['previewUrl']))
                                <div class="pcm-block-picker__preview">
                                    <img
                                        src="{{ $block['previewUrl'] }}"
                                        alt=""
                                        loading="lazy"
                                    />
                                </div>
                            @endif

                            <div class="pcm-block-picker__card-body">
                                @if (filled($block['icon']))
                                    <div class="pcm-block-picker__icon" aria-hidden="true">
                                        {{ \Filament\Support\generate_icon_html($block['icon'], size: \Filament\Support\Enums\IconSize::Medium) }}
                                    </div>
                                @endif

                                <div class="pcm-block-picker__content">
                                    <p class="pcm-block-picker__label">
                                        {{ $block['label'] }}
                                    </p>

                                    @if (filled($block['description']))
                                        <p class="pcm-block-picker__description">
                                            {{ $block['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="pcm-block-picker__empty">
                Aucun bloc disponible.
            </p>
        @endforelse

        <p
            x-show="query.trim() !== '' && ! hasAnyMatch"
            class="pcm-block-picker__empty"
        >
            Aucun bloc ne correspond à votre recherche.
        </p>
    </div>
</div>
