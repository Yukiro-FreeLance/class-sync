<?php

namespace App\Livewire\Help;

use App\Services\Help\UserManualService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Help & User Manual')]
class Index extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'manual';

    #[Url(as: 'section')]
    public ?string $activeSection = 'getting-started';

    public string $search = '';

    public string $faqCategory = 'all';

    public function mount(): void
    {
        if (! in_array($this->activeTab, ['manual', 'faq'], true)) {
            $this->activeTab = 'manual';
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function selectSection(string $sectionId): void
    {
        $this->activeSection = $sectionId;
        $this->activeTab = 'manual';
    }

    public function render(UserManualService $manual)
    {
        $sections = $manual->manualSections();
        $faqItems = $manual->faqItems();

        $sectionIds = collect($sections)->pluck('id')->all();
        if (! in_array($this->activeSection, $sectionIds, true)) {
            $this->activeSection = $sectionIds[0] ?? 'getting-started';
        }

        $query = strtolower(trim($this->search));

        $filteredSections = $sections;
        $filteredFaq = $faqItems;

        if ($query !== '') {
            $filteredSections = collect($sections)
                ->filter(function (array $section) use ($query) {
                    if (str_contains(strtolower($section['title']), $query)) {
                        return true;
                    }
                    if (str_contains(strtolower($section['summary']), $query)) {
                        return true;
                    }

                    foreach ($section['sections'] as $block) {
                        if (str_contains(strtolower($block['title']), $query)) {
                            return true;
                        }
                        foreach ($block['steps'] as $step) {
                            if (str_contains(strtolower($step), $query)) {
                                return true;
                            }
                        }
                    }

                    return false;
                })
                ->values()
                ->all();

            $filteredFaq = collect($faqItems)
                ->filter(fn (array $item) => str_contains(strtolower($item['question']), $query)
                    || str_contains(strtolower($item['answer']), $query)
                    || str_contains(strtolower($item['category']), $query))
                ->values()
                ->all();
        }

        if ($this->faqCategory !== 'all') {
            $filteredFaq = collect($filteredFaq)
                ->filter(fn (array $item) => $item['category'] === $this->faqCategory)
                ->values()
                ->all();
        }

        $activeSectionData = collect($filteredSections)->firstWhere('id', $this->activeSection)
            ?? collect($filteredSections)->first();

        if ($activeSectionData && $activeSectionData['id'] !== $this->activeSection) {
            $this->activeSection = $activeSectionData['id'];
        }

        return view('livewire.help.index', [
            'manualSections' => $filteredSections,
            'activeSectionData' => $activeSectionData,
            'faqItems' => $filteredFaq,
            'faqCategories' => $manual->faqCategories(),
        ]);
    }
}
