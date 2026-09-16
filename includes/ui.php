<?php
declare(strict_types=1);

/**
 * PHP helpers inspired by shadcn/ui component patterns.
 * @see https://ui.shadcn.com/docs/components
 */

function uiClass(string ...$parts): string
{
    $classes = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $classes[] = $part;
        }
    }
    return implode(' ', $classes);
}

function uiAttrs(array $attrs): string
{
    $html = '';
    foreach ($attrs as $key => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        if ($value === true) {
            $html .= ' ' . $key;
            continue;
        }
        $html .= ' ' . $key . '="' . e((string) $value) . '"';
    }
    return $html;
}

/** Button / link styled as shadcn Button */
function uiButton(
    string $label,
    string $href = '',
    string $variant = 'default',
    string $size = 'default',
    array $attrs = []
): string {
    $allowedVariants = ['default', 'outline', 'secondary', 'ghost', 'destructive', 'link'];
    $allowedSizes = ['default', 'sm', 'lg', 'icon'];
    if (!in_array($variant, $allowedVariants, true)) {
        $variant = 'default';
    }
    if (!in_array($size, $allowedSizes, true)) {
        $size = 'default';
    }

    $class = uiClass(
        'ui-btn',
        'ui-btn--' . $variant,
        'ui-btn--' . $size,
        (string) ($attrs['class'] ?? '')
    );
    unset($attrs['class']);

    if ($href !== '') {
        return '<a href="' . e($href) . '" class="' . e($class) . '"' . uiAttrs($attrs) . '>' . $label . '</a>';
    }

    $type = (string) ($attrs['type'] ?? 'button');
    unset($attrs['type']);
    return '<button type="' . e($type) . '" class="' . e($class) . '"' . uiAttrs($attrs) . '>' . $label . '</button>';
}

function uiBadge(string $label, string $variant = 'secondary', array $attrs = []): string
{
    $allowed = ['default', 'secondary', 'outline', 'destructive', 'ghost'];
    if (!in_array($variant, $allowed, true)) {
        $variant = 'secondary';
    }
    $class = uiClass('ui-badge', 'ui-badge--' . $variant, (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<span class="' . e($class) . '"' . uiAttrs($attrs) . '>' . e($label) . '</span>';
}

function uiSeparator(array $attrs = []): string
{
    $class = uiClass('ui-separator', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div role="separator" class="' . e($class) . '"' . uiAttrs($attrs) . '></div>';
}

function uiAlert(string $message, string $variant = 'default', ?string $title = null): string
{
    $allowed = ['default', 'destructive', 'success'];
    if (!in_array($variant, $allowed, true)) {
        $variant = 'default';
    }
    $html = '<div class="ui-alert ui-alert--' . e($variant) . '" role="status">';
    if ($title !== null && $title !== '') {
        $html .= '<div class="ui-alert-title">' . e($title) . '</div>';
    }
    $html .= '<div class="ui-alert-desc">' . e($message) . '</div></div>';
    return $html;
}

function uiEmpty(string $title, string $description = '', string $actionHtml = '', string $iconHtml = ''): string
{
    $html = '<div class="ui-empty" data-slot="empty">';
    $html .= '<div class="ui-empty-header" data-slot="empty-header">';
    if ($iconHtml !== '') {
        $html .= '<div class="ui-empty-media ui-empty-media--icon" data-slot="empty-media">' . $iconHtml . '</div>';
    }
    $html .= '<div class="ui-empty-title" data-slot="empty-title">' . e($title) . '</div>';
    if ($description !== '') {
        $html .= '<div class="ui-empty-desc" data-slot="empty-description">' . e($description) . '</div>';
    }
    $html .= '</div>';
    if ($actionHtml !== '') {
        $html .= '<div class="ui-empty-action" data-slot="empty-content">' . $actionHtml . '</div>';
    }
    $html .= '</div>';
    return $html;
}

/** @param list<array{label:string,href:string,active?:bool}> $items */
function uiBreadcrumb(array $items): string
{
    $html = '<nav class="ui-breadcrumb" aria-label="Breadcrumb"><ol class="ui-breadcrumb-list">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $label = (string) ($item['label'] ?? '');
        $href = (string) ($item['href'] ?? '');
        $active = !empty($item['active']) || $i === $last;
        $html .= '<li class="ui-breadcrumb-item">';
        if ($i > 0) {
            $html .= '<span class="ui-breadcrumb-sep" aria-hidden="true">/</span>';
        }
        if ($active || $href === '') {
            $html .= '<span class="ui-breadcrumb-page"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</span>';
        } else {
            $html .= '<a class="ui-breadcrumb-link" href="' . e($href) . '">' . e($label) . '</a>';
        }
        $html .= '</li>';
    }
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Tabs / filter links inspired by shadcn Tabs + Toggle Group
 * @param list<array{label:string,href:string,active?:bool,count?:int|null}> $tabs
 */
function uiTabs(array $tabs, string $ariaLabel = 'ตัวกรอง'): string
{
    $html = '<div class="ui-tabs" data-slot="tabs">';
    $html .= '<div class="ui-tabs-list" role="tablist" aria-label="' . e($ariaLabel) . '">';
    foreach ($tabs as $tab) {
        $active = !empty($tab['active']);
        $label = (string) ($tab['label'] ?? '');
        $href = (string) ($tab['href'] ?? '#');
        $count = $tab['count'] ?? null;
        $class = uiClass('ui-tabs-trigger', $active ? 'is-active' : '');
        $html .= '<a role="tab" aria-selected="' . ($active ? 'true' : 'false') . '" class="' . e($class) . '" href="' . e($href) . '">';
        $html .= e($label);
        if ($count !== null) {
            $html .= '<span class="ui-tabs-count">' . e((string) $count) . '</span>';
        }
        $html .= '</a>';
    }
    $html .= '</div></div>';
    return $html;
}

function uiAspectRatio(string $innerHtml, string $ratio = '16/10', array $attrs = []): string
{
    $class = uiClass('ui-aspect', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div class="' . e($class) . '" style="--ui-aspect:' . e($ratio) . '"' . uiAttrs($attrs) . '>'
        . '<div class="ui-aspect-inner">' . $innerHtml . '</div></div>';
}

function uiCardOpen(array $attrs = []): string
{
    $class = uiClass('ui-card', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div data-slot="card" class="' . e($class) . '"' . uiAttrs($attrs) . '>';
}

function uiCardClose(): string
{
    return '</div>';
}

function uiCardHeaderOpen(array $attrs = []): string
{
    $class = uiClass('ui-card-header', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div data-slot="card-header" class="' . e($class) . '"' . uiAttrs($attrs) . '>';
}

function uiCardContentOpen(array $attrs = []): string
{
    $class = uiClass('ui-card-content', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div data-slot="card-content" class="' . e($class) . '"' . uiAttrs($attrs) . '>';
}

function uiCardFooterOpen(array $attrs = []): string
{
    $class = uiClass('ui-card-footer', (string) ($attrs['class'] ?? ''));
    unset($attrs['class']);
    return '<div data-slot="card-footer" class="' . e($class) . '"' . uiAttrs($attrs) . '>';
}

function uiCardCloseSection(): string
{
    return '</div>';
}

function uiCardTitle(string $html, bool $escape = true): string
{
    return '<div data-slot="card-title" class="ui-card-title">' . ($escape ? e($html) : $html) . '</div>';
}

function uiCardDescription(string $html, bool $escape = true): string
{
    return '<div data-slot="card-description" class="ui-card-description">' . ($escape ? e($html) : $html) . '</div>';
}

function uiCardAction(string $html): string
{
    return '<div data-slot="card-action" class="ui-card-action">' . $html . '</div>';
}
