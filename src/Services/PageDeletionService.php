<?php

namespace Xavcha\PageContentManager\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Xavcha\PageContentManager\Enums\DeletedPageResponseType;
use Xavcha\PageContentManager\Models\Page;

class PageDeletionService
{
    public function softDelete(
        Page $page,
        DeletedPageResponseType $responseType,
        ?int $redirectTargetPageId = null,
        ?string $redirectTargetUrl = null,
    ): void {
        if ($page->isHome()) {
            throw new \RuntimeException('La page Home ne peut pas être supprimée.');
        }

        $this->validatePolicy($page, $responseType, $redirectTargetPageId, $redirectTargetUrl);

        $page->fill([
            'deleted_response_type' => $responseType,
            'redirect_target_page_id' => $responseType === DeletedPageResponseType::RedirectToPage
                ? $redirectTargetPageId
                : null,
            'redirect_target_url' => $responseType === DeletedPageResponseType::RedirectToUrl
                ? $redirectTargetUrl
                : null,
        ]);

        $page->save();
        $page->delete();
    }

    public function restore(Page $page): void
    {
        if (! $page->trashed()) {
            throw new \RuntimeException('Cette page n\'est pas dans la corbeille.');
        }

        $page->restore();

        $page->forceFill([
            'deleted_response_type' => null,
            'redirect_target_page_id' => null,
            'redirect_target_url' => null,
        ])->save();
    }

    public function forceDelete(Page $page): void
    {
        if ($page->isHome()) {
            throw new \RuntimeException('La page Home ne peut pas être supprimée.');
        }

        $page->forceDelete();
    }

    public function defaultResponseType(): DeletedPageResponseType
    {
        $configured = config('page-content-manager.deletion.default_response_type', DeletedPageResponseType::Gone->value);

        return DeletedPageResponseType::tryFrom((string) $configured)
            ?? DeletedPageResponseType::Gone;
    }

    protected function validatePolicy(
        Page $page,
        DeletedPageResponseType $responseType,
        ?int $redirectTargetPageId,
        ?string $redirectTargetUrl,
    ): void {
        $data = [
            'deleted_response_type' => $responseType->value,
            'redirect_target_page_id' => $redirectTargetPageId,
            'redirect_target_url' => $redirectTargetUrl,
        ];

        $rules = [
            'deleted_response_type' => ['required', 'in:' . implode(',', array_column(DeletedPageResponseType::cases(), 'value'))],
            'redirect_target_page_id' => [
                'nullable',
                'integer',
                'required_if:deleted_response_type,' . DeletedPageResponseType::RedirectToPage->value,
                'prohibited_unless:deleted_response_type,' . DeletedPageResponseType::RedirectToPage->value,
            ],
            'redirect_target_url' => [
                'nullable',
                'string',
                'max:2048',
                'url',
                'required_if:deleted_response_type,' . DeletedPageResponseType::RedirectToUrl->value,
                'prohibited_unless:deleted_response_type,' . DeletedPageResponseType::RedirectToUrl->value,
            ],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($responseType === DeletedPageResponseType::RedirectToPage) {
            $target = Page::query()->find($redirectTargetPageId);

            if (! $target) {
                throw ValidationException::withMessages([
                    'redirect_target_page_id' => 'La page cible de redirection est introuvable.',
                ]);
            }

            if ($target->trashed()) {
                throw ValidationException::withMessages([
                    'redirect_target_page_id' => 'La page cible ne peut pas être dans la corbeille.',
                ]);
            }

            if ($target->id === $page->id) {
                throw ValidationException::withMessages([
                    'redirect_target_page_id' => 'Une page ne peut pas rediriger vers elle-même.',
                ]);
            }

            if (! $target->isPublished()) {
                throw ValidationException::withMessages([
                    'redirect_target_page_id' => 'La page cible doit être publiée.',
                ]);
            }
        }
    }
}
