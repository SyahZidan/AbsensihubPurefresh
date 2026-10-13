<?php

function paginate_params(int $page, int $perPage): array
{
    $page = max(1, $page);
    $perPage = max(5, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    return ['page' => $page, 'per_page' => $perPage, 'offset' => $offset];
}

function pagination_links(int $total, int $page, int $perPage, string $baseRoute, array $query = []): string
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="mt-4 flex flex-wrap items-center justify-center gap-2 text-sm">';
    for ($p = 1; $p <= $totalPages; $p++) {
        if ($p > 1 && $p < $totalPages && abs($p - $page) > 2) {
            if ($p === 2 || $p === $totalPages - 1) {
                $html .= '<span class="px-1 text-slate-400">…</span>';
            }
            continue;
        }
        $query['p'] = $p;
        $url = route_url($baseRoute, $query);
        $active = $p === $page
            ? 'bg-slate-900 text-white'
            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50';
        $html .= '<a href="' . e($url) . '" class="rounded-lg px-3 py-1.5 font-semibold ' . $active . '">' . $p . '</a>';
    }
    $html .= '</nav>';

    return $html;
}
