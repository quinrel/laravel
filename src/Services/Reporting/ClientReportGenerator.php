<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Reporting;

use Illuminate\Support\Facades\View;
use Quinrel\Laravel\Services\Analytics\Ga4Client;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;

class ClientReportGenerator
{
    public function __construct(
        protected Ga4Client $ga4Client,
        protected PageSpeedInsights $pageSpeed
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generateData(string $url): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'url' => $url,
            'pagespeed' => $this->pageSpeed->fetch($url),
            'analytics' => [
                'sessions' => $this->ga4Client->getSessionTrends(),
                'top_pages' => $this->ga4Client->getTopLandingPages(),
            ],
        ];
    }

    public function generateJson(string $url): string
    {
        $data = $this->generateData($url);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function generateHtml(string $url): string
    {
        $data = $this->generateData($url);

        if (! View::exists('quinrel::reports.client')) {
            $safeUrl = e($url);
            $safeJson = e((string) json_encode($data, JSON_PRETTY_PRINT));

            return "<html><body><h1>SEO Report for {$safeUrl}</h1><pre>{$safeJson}</pre></body></html>";
        }

        /** @var view-string $viewName */
        $viewName = 'quinrel::reports.client';

        return View::make($viewName, ['report' => $data])->render();
    }

    public function generateCsv(string $url): string
    {
        $data = $this->generateData($url);
        $output = fopen('php://temp', 'r+');
        if (! $output) {
            return '';
        }

        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Generated At', $data['generated_at']]);
        fputcsv($output, ['URL', $data['url']]);

        $pagespeed = $data['pagespeed'];
        if (isset($pagespeed['score'])) {
            fputcsv($output, ['PageSpeed Score', (string) $pagespeed['score']]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv !== false ? $csv : '';
    }
}
