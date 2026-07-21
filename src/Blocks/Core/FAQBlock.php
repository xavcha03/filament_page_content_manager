<?php

namespace Xavcha\PageContentManager\Blocks\Core;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\TextInput;
use Xavcha\PageContentManager\Blocks\Concerns\HasMcpMetadata;
use Xavcha\PageContentManager\Blocks\Contracts\BlockInterface;

class FAQBlock implements BlockInterface
{
    use HasMcpMetadata;

    public static function getType(): string
    {
        return 'faq';
    }

    public static function getGroup(): string
    {
        return 'Contenu';
    }

    public static function getDescription(): string
    {
        return 'Questions / réponses accordéon pour les FAQ.';
    }

    public static function make(): Block
    {
        return Block::make('faq')
            ->label('Section FAQ')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                TextInput::make('titre')
                    ->label('Titre principal')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                Repeater::make('faqs')
                    ->label('Questions fréquentes')
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->required()
                            ->maxLength(300)
                            ->columnSpanFull(),

                        static::makeAnswerEditor(),
                    ])
                    ->minItems(1)
                    ->maxItems(50)
                    ->defaultItems(5)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'Question')
                    ->columnSpanFull(),
            ]);
    }

    protected static function makeAnswerEditor(): RichEditor
    {
        return RichEditor::make('answer')
            ->label('Réponse')
            ->required()
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                [
                    ToolbarButtonGroup::make('Typo', [
                        'lead',
                        'small',
                        'code',
                        'highlight',
                        'textColor',
                        'clearFormatting',
                    ])->textualButtons(),
                ],
                [
                    ToolbarButtonGroup::make('Titres', [
                        'paragraph',
                        'h3',
                        'h4',
                        'h5',
                        'h6',
                    ])->textualButtons(),
                ],
                [
                    ToolbarButtonGroup::make('Align', [
                        'alignStart',
                        'alignCenter',
                        'alignEnd',
                        'alignJustify',
                    ]),
                ],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                ['table'],
                [
                    ToolbarButtonGroup::make('Layout', [
                        'grid',
                        'gridDelete',
                        'details',
                    ])->textualButtons(),
                ],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => [
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'code',
                    'highlight',
                    'textColor',
                    'clearFormatting',
                    'link',
                ],
                'heading' => [
                    'paragraph',
                    'h3',
                    'h4',
                    'h5',
                    'h6',
                ],
                'table' => [
                    'tableAddColumnBefore',
                    'tableAddColumnAfter',
                    'tableDeleteColumn',
                    'tableAddRowBefore',
                    'tableAddRowAfter',
                    'tableDeleteRow',
                    'tableMergeCells',
                    'tableSplitCell',
                    'tableToggleHeaderRow',
                    'tableToggleHeaderCell',
                    'tableDelete',
                ],
            ])
            ->columnSpanFull();
    }

    public static function transform(array $data): array
    {
        return [
            'type' => 'faq',
            'titre' => $data['titre'] ?? '',
            'faqs' => static::transformFAQs($data['faqs'] ?? []),
        ];
    }

    protected static function transformFAQs(array $faqs): array
    {
        return array_map(function ($faq) {
            if (!is_array($faq)) {
                return $faq;
            }

            return [
                'question' => $faq['question'] ?? '',
                'answer' => static::normalizeAnswer($faq['answer'] ?? ''),
            ];
        }, $faqs);
    }

    protected static function normalizeAnswer(string $answer): string
    {
        if ($answer === '') {
            return '';
        }

        // Compatibilité avec les anciennes réponses saisies en texte brut (Textarea).
        if ($answer === strip_tags($answer)) {
            $paragraphs = array_filter(array_map('trim', preg_split('/\R+/', $answer) ?: []));

            if ($paragraphs === []) {
                return '';
            }

            return implode('', array_map(
                static fn (string $paragraph): string => '<p>'.e($paragraph).'</p>',
                $paragraphs
            ));
        }

        return $answer;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMcpFields(): array
    {
        return [
            [
                'name' => 'titre',
                'label' => 'Titre principal',
                'type' => 'string',
                'required' => true,
                'max_length' => 200,
            ],
            [
                'name' => 'faqs',
                'label' => 'Questions',
                'type' => 'array',
                'required' => true,
                'description' => 'Liste de questions/reponses',
                'items' => [
                    [
                        'name' => 'question',
                        'type' => 'string',
                        'required' => true,
                        'max_length' => 300,
                    ],
                    [
                        'name' => 'answer',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Reponse au format HTML/rich text',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getMcpExample(): array
    {
        return [
            'titre' => 'Questions frequentes',
            'faqs' => [
                [
                    'question' => 'Quels sont vos delais ?',
                    'answer' => '<p>En general <strong>2 a 4 semaines</strong> selon le projet.</p>',
                ],
                [
                    'question' => 'Proposez-vous un devis ?',
                    'answer' => '<p>Oui, un devis detaille est fourni avant demarrage.</p>',
                ],
            ],
        ];
    }
}
