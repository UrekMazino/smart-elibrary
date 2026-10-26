<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Content Security Policy (SecurityHeaders) runs Report-Only until no page needs inline JavaScript
 * outside a nonce-tagged <script>: docs/not-implemented/CSP_ROLLOUT.md. This test only lets those counts
 * go down. After converting handlers, lower the baseline to the new count (the message says it); once
 * both reach 0, the policy can be enforced (CSP_ENFORCE=true).
 */
class CspRatchetTest extends TestCase
{
    /** onclick="…" and the like, in templates and in admin scripts that build HTML (98 on 2026-10-26; none left). */
    private const HANDLERS = 0;

    /** href="javascript:…" links (103 on 2026-10-26; none left). */
    private const JAVASCRIPT_LINKS = 0;

    /** @return array<string, string> file => contents */
    private function sources(): array
    {
        $files = [];
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach (new \RegexIterator($views, '/\.blade\.php$/') as $file) {
            if (! str_contains((string) $file, DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR)) {   // mail clients run no scripts
                $files[(string) $file] = file_get_contents((string) $file);
            }
        }
        foreach (glob(public_path('js/admin/*.js')) ?: [] as $file) {
            $files[$file] = file_get_contents($file);
        }
        return $files;
    }

    private function occurrences(string $pattern): int
    {
        return array_sum(array_map(fn ($code) => preg_match_all($pattern, $code), $this->sources()));
    }

    public function test_every_inline_script_carries_the_nonce()
    {
        $missing = [];
        foreach ($this->sources() as $file => $code) {
            if (preg_match_all('/<script(?=[\s>])(?![^>]*\bsrc=)(?![^>]*\bnonce=)/i', $code)) {
                $missing[] = str_replace(base_path() . '/', '', $file);
            }
        }
        $this->assertSame([], $missing, 'Give inline <script> tags nonce="{{ cspNonce() }}" (or move the code into a file).');
    }

    public function test_inline_event_handlers_only_go_down()
    {
        $now = $this->occurrences('/\son[a-z]+\s*=\s*\\\\?["\']/i');
        $this->assertLessThanOrEqual(self::HANDLERS, $now,
            "New inline event handlers (onclick=\"…\"): use addEventListener in a nonce-tagged script instead.");
        if ($now < self::HANDLERS) {
            $this->markTestIncomplete("Inline handlers are down to $now: lower CspRatchetTest::HANDLERS to $now.");
        }
    }

    public function test_javascript_links_only_go_down()
    {
        $now = $this->occurrences('/href\s*=\s*\\\\?["\']javascript:/i');
        $this->assertLessThanOrEqual(self::JAVASCRIPT_LINKS, $now,
            'New href="javascript:…" links: use a <button> or href="#" with a listener instead.');
        if ($now < self::JAVASCRIPT_LINKS) {
            $this->markTestIncomplete("javascript: links are down to $now: lower CspRatchetTest::JAVASCRIPT_LINKS to $now.");
        }
    }
}
