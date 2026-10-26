<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Schema;

class SchemaGenerator
{
    /**
     * @var array<int, SchemaBuilder|array<string, mixed>>
     */
    protected array $schemas = [];

    /**
     * Add a custom raw JSON-LD array or string.
     *
     * @param  array<string, mixed>|string  $schema
     */
    public function addCustom($schema): self
    {
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (is_array($decoded)) {
                $this->schemas[] = $decoded;
            }
        } elseif (is_array($schema)) {
            $this->schemas[] = $schema;
        }

        return $this;
    }

    public function add(SchemaBuilder $builder): self
    {
        $this->schemas[] = $builder;

        return $this;
    }

    /**
     * Generate an automated E-E-A-T Person/Author schema linked to an Organization.
     *
     * @param  array<string, string>  $socialLinks
     * @param  array<string, string>  $credentials  e.g. ['alumniOf' => 'University Name', 'jobTitle' => 'Expert']
     */
    public function buildEeatAuthor(string $name, string $url, string $orgName, array $socialLinks = [], array $credentials = []): SchemaBuilder
    {
        $person = SchemaBuilder::make('Person')
            ->id($url.'#author')
            ->set('name', $name)
            ->set('url', $url);

        if (! empty($socialLinks)) {
            $person->set('sameAs', array_values($socialLinks));
        }

        if (! empty($credentials['jobTitle'])) {
            $person->set('jobTitle', $credentials['jobTitle']);
        }

        if (! empty($credentials['alumniOf'])) {
            $person->set('alumniOf', [
                '@type' => 'Organization',
                'name' => $credentials['alumniOf'],
            ]);
        }

        $person->set('worksFor', [
            '@type' => 'Organization',
            'name' => $orgName,
        ]);

        return $person;
    }

    public function buildArticle(string $headline, string $url, string $datePublished, string $authorName, string $publisherName): SchemaBuilder
    {
        return SchemaBuilder::make('Article')
            ->set('headline', $headline)
            ->set('url', $url)
            ->set('datePublished', $datePublished)
            ->set('author', [
                '@type' => 'Person',
                'name' => $authorName,
            ])
            ->set('publisher', [
                '@type' => 'Organization',
                'name' => $publisherName,
            ]);
    }

    /**
     * @param  array<string, string>  $crumbs  Key is name, value is URL
     */
    public function buildBreadcrumb(array $crumbs): SchemaBuilder
    {
        $itemListElement = [];
        $position = 1;
        foreach ($crumbs as $name => $url) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return SchemaBuilder::make('BreadcrumbList')
            ->set('itemListElement', $itemListElement);
    }

    /**
     * @param  array<string, string>  $questionsAndAnswers  Key is Question, value is Answer
     */
    public function buildFaq(array $questionsAndAnswers): SchemaBuilder
    {
        $mainEntity = [];
        foreach ($questionsAndAnswers as $question => $answer) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return SchemaBuilder::make('FAQPage')
            ->set('mainEntity', $mainEntity);
    }

    public function buildImageObject(string $url, int $width, int $height, string $caption = ''): SchemaBuilder
    {
        $image = SchemaBuilder::make('ImageObject')
            ->set('url', $url)
            ->set('width', $width)
            ->set('height', $height);

        if (! empty($caption)) {
            $image->set('caption', $caption);
        }

        return $image;
    }

    public function render(): string
    {
        if (empty($this->schemas)) {
            return '';
        }

        $graphs = [];
        foreach ($this->schemas as $schema) {
            if ($schema instanceof SchemaBuilder) {
                $graphs[] = $schema->toArray();
            } else {
                $graphs[] = $schema;
            }
        }

        // Output as a Graph array if there are multiple, or just the single object
        $output = count($graphs) === 1 ? $graphs[0] : ['@context' => 'https://schema.org', '@graph' => $graphs];

        $json = json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?: '{}';

        return '<script type="application/ld+json">'.PHP_EOL.$json.PHP_EOL.'</script>';
    }
}
