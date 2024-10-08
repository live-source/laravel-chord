@props(['page'])
<div>
    @foreach ($page->blocks() as $block)
        @if($block->isLivewireComponent())
            <livewire:dynamic-component :is="$block->getComponent()" :block="$block" />
        @else
            <x-dynamic-component
                :component="$block->getComponent()"
                :block="$block"
            />
        @endif
    @endforeach
</div>
