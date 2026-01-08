<?php

namespace Xavcha\PageContentManager\Services\Blocks\Transformers\Core;

use Xavcha\PageContentManager\Services\Blocks\Contracts\BlockTransformerInterface;

class FAQBlockTransformer implements BlockTransformerInterface
{
    public function getType(): string
    {
        return 'faq';
    }

    public function transform(array $data): array
    {
        $transformed = [
            'type' => 'faq',
            'titre' => $data['titre'] ?? '',
            'faqs' => $this->transformFAQs($data['faqs'] ?? []),
        ];

        return $transformed;
    }

    protected function transformFAQs(array $faqs): array
    {
        return array_map(function ($faq) {
            if (!is_array($faq)) {
                return $faq;
            }

            return [
                'question' => $faq['question'] ?? '',
                'answer' => $faq['answer'] ?? '',
            ];
        }, $faqs);
    }
}

