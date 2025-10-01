<?php

declare(strict_types=1);

namespace Plugs\SEO;

class SeoMetaGenerator
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Generate Open Graph tags.
     */
    public function generateOpenGraphTags(array $data): string
    {
        $tags = array_merge($this->config['og_defaults'] ?? [], $data);
        $html = '';

        foreach ($tags as $property => $content) {
            if (!empty($content)) {
                $property = htmlspecialchars($property, ENT_QUOTES, 'UTF-8');
                $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $html .= "<meta property=\"og:$property\" content=\"$content\">\n";
            }
        }

        return $html;
    }

    /**
     * Generate Twitter Card tags.
     */
    public function generateTwitterCardTags(array $data): string
    {
        $tags = array_merge($this->config['twitter_defaults'] ?? [], $data);
        $html = '';

        foreach ($tags as $name => $content) {
            if (!empty($content)) {
                $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $html .= "<meta name=\"twitter:$name\" content=\"$content\">\n";
            }
        }

        return $html;
    }

    /**
     * Generate JSON-LD structured data.
     */
    public function generateStructuredData(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return "<script type=\"application/ld+json\">\n$json\n</script>";
    }
}