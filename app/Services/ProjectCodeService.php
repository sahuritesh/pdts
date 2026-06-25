<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectCodeService
{
    /**
     * Generate a unique project ID (project_code), e.g. PRJ-2026-0001.
     */
    public function generate(?int $excludeProjectId = null): string
    {
        $prefix = 'PRJ-' . date('Y') . '-';
        $seq = $this->nextSequenceForPrefix($prefix);

        do {
            $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            if (!$this->codeExists($code, $excludeProjectId)) {
                return $code;
            }
            $seq++;
        } while ($seq < 100000);

        return $prefix . strtoupper(substr(uniqid('', true), -6));
    }

    /**
     * Use submitted code when present; otherwise generate on create.
     *
     * @param  array<string, mixed>  $postData
     */
    public function resolveForSave(array &$postData, string $operation, ?int $projectId = null): void
    {
        $code = trim((string) ($postData['project_code'] ?? ''));
        if ($code !== '') {
            $postData['project_code'] = $code;

            return;
        }

        if ($operation === 'Add') {
            $postData['project_code'] = $this->generate();
        }
    }

    private function nextSequenceForPrefix(string $prefix): int
    {
        $latest = DB::table('tbl_projects')
            ->where('is_delete', 0)
            ->where('project_code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('project_code');

        if (!$latest || !preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest, $matches)) {
            return 1;
        }

        return max(1, (int) $matches[1] + 1);
    }

    private function codeExists(string $code, ?int $excludeProjectId = null): bool
    {
        $query = DB::table('tbl_projects')
            ->where('is_delete', 0)
            ->where('project_code', $code);

        if ($excludeProjectId) {
            $query->where('id', '!=', $excludeProjectId);
        }

        return $query->exists();
    }
}
