<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Data\GscInspectionDiagnosis;
use Quinrel\Laravel\Models\QuinrelMeta;

class GscIndexStatus
{
    public function __construct(
        protected ?GscClient $client = null,
        protected ?GscInspectionNormalizer $normalizer = null,
        protected ?GscActionPlanGenerator $actionPlanGenerator = null,
    ) {
        $this->client ??= app(GscClient::class);
        $this->normalizer ??= app(GscInspectionNormalizer::class);
        $this->actionPlanGenerator ??= app(GscActionPlanGenerator::class);
    }

    /**
     * Checks raw index status using the Google Search Console URL Inspection API.
     *
     * @return array<string, mixed>
     */
    public function check(string $url): array
    {
        return $this->client ? $this->client->inspectUrl($url) : [];
    }

    /**
     * Inspect a URL and return a normalized, strongly-typed diagnosis DTO.
     *
     * @param  array<string, mixed>|null  $rawPayload
     */
    public function diagnose(string $url, ?array $rawPayload = null): GscInspectionDiagnosis
    {
        $payload = $rawPayload ?? $this->check($url);

        return ($this->normalizer ?? new GscInspectionNormalizer)->normalize($url, $payload);
    }

    /**
     * Inspect a URL and produce both the normalized diagnosis and prioritized action plan.
     *
     * @param  array<string, mixed>|null  $rawPayload
     * @return array{
     *     diagnosis: GscInspectionDiagnosis,
     *     actions: array<int, array{
     *         id: string,
     *         severity: string,
     *         priority: int,
     *         title: string,
     *         description: string,
     *         remediation: string,
     *         autofix_action: ?string,
     *         metadata: array<string, mixed>
     *     }>
     * }
     */
    public function inspectAndPlan(string $url, ?QuinrelMeta $meta = null, string $content = '', ?array $rawPayload = null): array
    {
        $diagnosis = $this->diagnose($url, $rawPayload);
        $actions = ($this->actionPlanGenerator ?? new GscActionPlanGenerator)->generate($diagnosis, $meta, $content);

        $result = [
            'diagnosis' => $diagnosis,
            'actions' => $actions,
        ];

        Cache::put('quinrel:gsc-diagnosis:'.md5($url), $result, now()->addHours(24));

        return $result;
    }
}
