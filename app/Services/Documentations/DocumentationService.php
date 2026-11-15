<?php

namespace App\Services\Documentations;

use App\Services\Documentations\Interfaces\DocumentationsInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;

class DocumentationService
{
    public function homePage()
    {
        $data = app(DocumentationsInterface::class)->data(request: []);
        $allData = $data['data'];

        // Documentation-only display names and de-duplication. The stored rows,
        // HTTP methods, routes and payloads are all left untouched.
        $nameOverrides = config('documentation.endpoint_names', []);

        $allData = $allData
            ->map(function ($item) use ($nameOverrides) {
                $method = strtoupper((string) $item->method);
                $key = $method.' '.$item->route;
                $patternKey = $method.' '.$this->normalizeNumericRoute($item->route);

                if (isset($nameOverrides[$key])) {
                    $item->name = $nameOverrides[$key];
                } elseif (isset($nameOverrides[$patternKey])) {
                    $item->name = $nameOverrides[$patternKey];
                }

                return $item;
            })
            ->unique(fn ($item) => strtoupper((string) $item->method).' '.$item->route.' '.$item->name)
            ->values();

        $overViewWithIntroduction = $allData->where('section', 0);
        $commonErrors = $allData->where('section', 2);

        // Organise API endpoints by consumer section → module, derived automatically
        // from each endpoint's URL (config/documentation.php: consumers + modules).
        // New modules are placed automatically; no manual per-endpoint maintenance.
        $grouped = [];
        foreach ($allData->where('section', 1) as $item) {
            [$section, $module] = $this->classify($item->route, $item->method);
            $grouped[$section][$module][] = $item;
        }

        // Emit sections in the configured consumer order; append any extras last.
        $apiSections = [];
        foreach (array_keys(config('documentation.consumers', [])) as $section) {
            if (isset($grouped[$section])) {
                $apiSections[$section] = $grouped[$section];
            }
        }
        foreach ($grouped as $section => $modules) {
            if (! isset($apiSections[$section])) {
                $apiSections[$section] = $modules;
            }
        }

        foreach ($apiSections as $section => $modules) {
            foreach ($modules as $module => $items) {
                $apiSections[$section][$module] = $this->groupModule($module, $items);
            }

            $apiSections[$section] = $this->sortModules($section, $apiSections[$section]);
        }

        return view('docs-api', [
            'data' => $allData,
            'overViewWithIntroduction' => $overViewWithIntroduction,
            'commonErrors' => $commonErrors,
            'apiSections' => $apiSections,
            'baseUrl' => rtrim((string) config('app.url'), '/').'/api/'.config('api.version'),
            'totalEndpoints' => $allData->where('section', 1)->count(),
        ]);
    }

    /**
     * Organise a module's endpoints into folders.
     *
     * Folder membership is declared per endpoint with the `group:` argument in
     * the module's DocumentationCases (see the how-to at the top of that file).
     * Endpoints without a group keep the automatic route-based grouping as a
     * fallback, so modules that have not been migrated yet keep working.
     */
    private function groupModule(string $module, array $items): array
    {
        $threshold = (int) config('documentation.group_threshold', 5);
        $autoGroup = count($items) > $threshold;
        $grouped = [];
        $hasExplicitGroup = false;

        foreach ($items as $item) {
            $declared = filled($item->group ?? null) && $item->group !== $module;
            $hasExplicitGroup = $hasExplicitGroup || $declared;

            $label = $declared
                ? $item->group
                : ($autoGroup ? $this->groupFor($item->route, $module, $item->name) : null);

            $key = $label ? \Illuminate\Support\Str::slug($label) : 'all';

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'name' => $label,
                    'items' => [],
                ];
            }

            $grouped[$key]['items'][] = $item;
        }

        if (count($grouped) <= 1) {
            return [
                'total' => count($items),
                'groups' => [[
                    'key' => 'all',
                    'name' => null,
                    'items' => $items,
                ]],
            ];
        }

        $groups = array_values($grouped);

        // Folder order. When the module declares folders in DocumentationCases we
        // keep that declaration order. Otherwise (legacy modules) the explicit
        // config/documentation.php `group_order` map applies.
        $order = config('documentation.group_order', []);
        $firstSeen = [];
        foreach ($groups as $index => $group) {
            $firstSeen[$group['name']] = $index;
        }

        usort($groups, static function (array $a, array $b) use ($order, $firstSeen, $hasExplicitGroup): int {
            if ($hasExplicitGroup) {
                return ($firstSeen[$a['name']] ?? 0) <=> ($firstSeen[$b['name']] ?? 0);
            }

            $aOrder = $order[$a['name']] ?? PHP_INT_MAX;
            $bOrder = $order[$b['name']] ?? PHP_INT_MAX;

            return $aOrder <=> $bOrder
                ?: ($firstSeen[$a['name']] ?? 0) <=> ($firstSeen[$b['name']] ?? 0);
        });

        return [
            'total' => count($items),
            'groups' => $groups,
        ];
    }

    /**
     * Sort a section's modules using the configured display order.
     */
    private function sortModules(string $section, array $modules): array
    {
        $order = config('documentation.module_order', [])[$section] ?? [];
        $position = array_flip($order);

        uksort($modules, static function ($a, $b) use ($position): int {
            $aOrder = $position[$a] ?? PHP_INT_MAX;
            $bOrder = $position[$b] ?? PHP_INT_MAX;

            return $aOrder <=> $bOrder ?: strcmp((string) $a, (string) $b);
        });

        return $modules;
    }

    /**
     * Resolve a human-readable feature group from the endpoint route.
     */
    private function groupFor(?string $route, string $module, ?string $name = null): string
    {
        $segments = $this->normalizePath($route);
        $consumerPrefixes = ['dashboard', 'admin', 'web', 'public', 'internal', 'security', 'theater', 'group', 'tenant', 'user'];
        $isUser = ($segments[0] ?? null) === 'user';
        $isDashboard = ($segments[0] ?? null) === 'dashboard';

        if (! empty($segments) && in_array($segments[0], $consumerPrefixes, true)) {
            array_shift($segments);
        }

        $segments = array_values(array_filter(
            $segments,
            static fn ($segment) => ! is_numeric($segment) && ! str_starts_with($segment, '{'),
        ));

        $resource = $segments[0] ?? 'general';
        $tail = array_slice($segments, 1);
        $first = $tail[0] ?? null;
        $action = $first ?? $resource;
        $has = static fn (string $segment): bool => in_array($segment, $tail, true);
        $lowerName = strtolower((string) $name);

        $isRecycle = in_array('trash', $tail, true)
            || in_array('restore', $tail, true)
            || in_array('force-delete', $tail, true)
            || in_array('force', $tail, true);

        if ($isRecycle) {
            if ($module === 'Administration') {
                return \Illuminate\Support\Str::singular(\Illuminate\Support\Str::headline($resource)).' Recycle Bin';
            }

            return 'Recycle Bin';
        }

        if ($first === 'search') {
            return 'Search';
        }

        return match ($module) {
            'Authentication' => $this->authenticationGroup($action, $lowerName),
            'Administration' => $this->administrationGroup($resource, $tail, $has),
            'Users' => $isUser ? $this->usersGroup($action, $lowerName) : 'User Management',
            'Settings' => 'Management',
            'Locations' => 'Lookups',
            'Security' => 'Security',
            default => 'General',
        };
    }

    private function authenticationGroup(?string $first, string $lowerName): string
    {
        if (
            str_contains((string) $first, 'password')
            || str_contains((string) $first, 'forget')
            || str_contains($lowerName, 'password')
        ) {
            return 'Password Reset';
        }

        if (in_array($first, ['register', 'verify', 'resend-otp'], true)) {
            return 'Registration & Verification';
        }

        if (in_array($first, ['login', 'logout', 'refresh-token', 'me'], true)) {
            return 'Login & Session';
        }

        return 'Session & Security';
    }

    private function administrationGroup(string $resource, array $tail, callable $has): string
    {
        if ($resource === 'admins') {
            return $has('roles') ? 'Role Assignments' : 'Admins';
        }

        if ($resource === 'roles') {
            if ($has('admins') || $has('available-admins') || $has('permissions')) {
                return 'Role Assignments';
            }

            return 'Roles';
        }

        if ($resource === 'models') {
            return 'Models';
        }

        if ($resource === 'profile') {
            return 'Profile';
        }

        return \Illuminate\Support\Str::headline($resource);
    }

    private function usersGroup(?string $first, string $lowerName): string
    {
        if (str_contains($lowerName, 'password')) {
            return 'Password';
        }

        if (in_array($first, ['register', 'login', 'verify', 'forget-password', 'verify-forget-password', 'reset-password', 'resend-otp', 'refresh-token', 'logout'], true)) {
            return 'Authentication';
        }

        return 'Profile';
    }

    private function normalizePath(?string $route): array
    {
        $path = parse_url((string) $route, PHP_URL_PATH) ?: (string) $route;

        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn ($s) => $s !== '',
        ));

        while (
            ! empty($segments)
            && ($segments[0] === 'api' || preg_match('/^v\d+$/', $segments[0]) === 1)
        ) {
            array_shift($segments);
        }

        return $segments;
    }

    /**
     * Normalize numeric route segments so endpoint-name overrides also match
     * parameterized routes such as /country/6/cities.
     */
    private function normalizeNumericRoute(?string $route): string
    {
        return preg_replace('#/\d+(?=/|$)#', '/{id}', (string) $route);
    }

    /**
     * Map an endpoint to its [consumer section, module]. Explicit overrides
     * (config('documentation.overrides')) win — for endpoints whose routing
     * prefix does not reflect their real consumer (e.g. FAQs) — otherwise the
     * consumer/module are derived from the URL.
     */
    private function classify(?string $route, ?string $method): array
    {
        $path = implode('/', $this->normalizePath($route));

        foreach (config('documentation.overrides', []) as $override) {
            if (isset($override['method']) && strtoupper($override['method']) !== strtoupper((string) $method)) {
                continue;
            }

            if (isset($override['regex'])) {
                $matches = (bool) preg_match($override['regex'], $path);
            } else {
                $target = implode('/', $this->normalizePath($override['route'] ?? ''));
                $matches = ($override['exact'] ?? false)
                    ? $path === $target
                    : ($path === $target || str_starts_with($path, $target.'/'));
            }

            if ($matches) {
                return [
                    $override['consumer'] ?? $this->consumerFor($route),
                    $override['module'] ?? $this->moduleFor($route),
                ];
            }
        }

        return [$this->consumerFor($route), $this->moduleFor($route)];
    }

    /**
     * Resolve the API consumer section for an endpoint from its URL, using the
     * longest matching prefix in config('documentation.consumers').
     */
    private function consumerFor(?string $route): string
    {
        $path = implode('/', $this->normalizePath($route));
        $best = null;
        $bestLen = -1;

        foreach (config('documentation.consumers', []) as $section => $prefixes) {
            foreach ((array) $prefixes as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    if (strlen($prefix) > $bestLen) {
                        $best = $section;
                        $bestLen = strlen($prefix);
                    }
                }
            }
        }

        return $best ?? config('documentation.default_consumer', 'Admin Dashboard API');
    }

    /**
     * Resolve the feature/module label for an endpoint from its URL resource:
     * drop a single leading consumer-prefix segment (so 'tenant/team' keeps
     * 'dashboard'), then map the first remaining segment via
     * config('documentation.modules') with a title-cased fallback.
     */
    private function moduleFor(?string $route): string
    {
        $consumerPrefixes = ['dashboard', 'admin', 'web', 'public', 'internal', 'security', 'theater', 'group', 'tenant', 'user'];

        $segments = array_values(array_filter(
            $this->normalizePath($route),
            static fn ($s) => ! str_starts_with($s, '{'),
        ));

        if (! empty($segments) && in_array($segments[0], $consumerPrefixes, true)) {
            array_shift($segments);
        }

        $resource = $segments[0] ?? 'general';
        $map = config('documentation.modules', []);

        return $map[$resource] ?? (string) \Illuminate\Support\Str::of($resource)->replace('-', ' ')->title();
    }

    public function generateValidationDocs(string|array $source): array
    {
        $rules = $this->resolveRules($source);

        return collect($rules)
            ->map(function ($ruleSet, $field) {
                return [
                    'key' =>  $this->formatField($field),
                    'value' => $this->formatRule($ruleSet),
                ];
            })
            ->values()
            ->toArray();
    }
    // =============================
    private function formatField(string $field): string
    {
        if (str_contains($field, '.*.')) {
            return str_replace('.*.', '[].', $field);
        }

        if (str_ends_with($field, '.*')) {
            return str_replace('.*', '[]', $field);
        }

        return $field;
    }
    // =============================
    private function resolveRules(string|array $source): array
    {
        if (is_string($source) && class_exists($source)) {

            // ✅ مهم: نستخدم new مش app()
            $request = new $source();

            if (! $request instanceof FormRequest) {
                throw new \InvalidArgumentException('Class must be a FormRequest');
            }

            if (! method_exists($request, 'rules')) {
                return [];
            }

            return (array) $request->rules();
        }

        return $source;
    }
    // =============================
    private function formatRule($ruleSet): string
    {
        // ✅ لو string جاهز (عربي مثلا)
        if (is_string($ruleSet) && !str_contains($ruleSet, '|')) {
            return $ruleSet;
        }

        $descriptions = [];

        if (is_array($ruleSet)) {

            foreach ($ruleSet as $rule) {

                if (is_string($rule)) {
                    $this->parseRuleToText($rule, $descriptions);
                }

                elseif ($rule instanceof Enum) {
                    $reflection = new \ReflectionProperty(get_class($rule), 'type');
                    $reflection->setAccessible(true);
                    $enumClass = $reflection->getValue($rule);
                    $values = array_map(fn($c) => $c->value, $enumClass::cases());
                    $descriptions[] = 'enum: ' . implode(', ', $values);
                }

                elseif ($rule instanceof In) {
                    $reflection = new \ReflectionProperty(get_class($rule), 'values');
                    $reflection->setAccessible(true);
                    $values = $reflection->getValue($rule);
                    $descriptions[] = 'allowed: ' . implode(', ', $values);
                }

                else {
                    $descriptions[] = class_basename($rule);
                }
            }

        } else {
            $this->parseRuleToText($ruleSet, $descriptions);
        }

        // If a file field has a `max`, it is an upload size in kilobytes — label it
        // so the Frontend knows it is not a string-length limit.
        $isFileField = (bool) array_filter($descriptions, fn ($d) => in_array($d, ['file', 'image'], true) || str_starts_with($d, 'file types:') || str_starts_with($d, 'mime types:'));
        if ($isFileField) {
            $descriptions = array_map(
                fn ($d) => preg_match('/^max (\d+)$/', $d, $m) ? "max {$m[1]} KB (upload)" : $d,
                $descriptions
            );
        }

        // Make required/optional explicit: a field present in the rules without
        // `required`/`nullable`/`sometimes` is still required in Laravel.
        $hasReqOpt = (bool) array_filter($descriptions, fn ($d) => in_array($d, ['required', 'optional'], true) || str_starts_with($d, 'required if'));
        if (! $hasReqOpt && ! empty($descriptions)) {
            array_unshift($descriptions, 'required');
        }

        // ✅ remove duplicates
        $descriptions = array_unique($descriptions);

        return implode(', ', $descriptions);
    }
    // =============================
    private function parseRuleToText(string $ruleSet, array &$descriptions): void
    {
        foreach (explode('|', $ruleSet) as $rule) {

            if (str_contains($rule, ':')) {
                [$name, $value] = explode(':', $rule, 2);
            } else {
                $name = $rule;
                $value = null;
            }

            switch ($name) {

                case 'required':
                    $descriptions[] = 'required';
                    break;

                case 'required_if':
                    [$field, $val] = explode(',', $value);
                    $descriptions[] = "required if {$field} = {$val}";
                    break;

                case 'nullable':
                    $descriptions[] = 'optional';
                    break;

                case 'string':
                    $descriptions[] = 'string';
                    break;

                case 'integer':
                    $descriptions[] = 'integer';
                    break;

                case 'email':
                    $descriptions[] = 'valid email';
                    break;

                case 'boolean':
                    $descriptions[] = 'true/false';
                    break;

                case 'array':
                    $descriptions[] = 'array';
                    break;

                case 'digits':
                    $descriptions[] = "{$value} digits";
                    break;

                case 'max':
                    $descriptions[] = "max {$value}";
                    break;

                case 'min':
                    $descriptions[] = "min {$value}";
                    break;

                case 'in':
                    $descriptions[] = "allowed: " . str_replace(',', ', ', $value);
                    break;

                case 'unique':
                    $descriptions[] = "unique {$value}";
                    break;

                case 'exists':
                    $descriptions[] = "exists {$value}";
                    break;

                case 'image':
                    $descriptions[] = 'image';
                    break;

                case 'mimes':
                    $descriptions[] = "file types: {$value}";
                    break;

                case 'mimetypes':
                    $descriptions[] = "mime types: {$value}";
                    break;

                case 'sometimes':
                    $descriptions[] = 'optional';
                    break;

                case 'numeric':
                    $descriptions[] = 'number';
                    break;

                case 'date':
                    $descriptions[] = 'date (YYYY-MM-DD)';
                    break;

                case 'date_format':
                    $descriptions[] = "date format {$value}";
                    break;

                case 'file':
                    $descriptions[] = 'file';
                    break;

                case 'url':
                    $descriptions[] = 'valid URL';
                    break;

                case 'confirmed':
                    $descriptions[] = 'must match its *_confirmation field';
                    break;

                case 'after':
                    $descriptions[] = "after {$value}";
                    break;

                case 'after_or_equal':
                    $descriptions[] = "on or after {$value}";
                    break;

                case 'before':
                    $descriptions[] = "before {$value}";
                    break;

                case 'before_or_equal':
                    $descriptions[] = "on or before {$value}";
                    break;

                case 'between':
                    $descriptions[] = "between {$value}";
                    break;

                default:
                    $descriptions[] = $name;
            }
        }
    }

}
