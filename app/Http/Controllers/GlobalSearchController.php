<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Meilisearch\Client as MeilisearchClient;
use Meilisearch\Contracts\SearchQuery;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            /** @var MeilisearchClient $client */
            $client = app(MeilisearchClient::class);

            // Only query indices that actually exist — avoids a full failure when
            // an index has never been populated (e.g. no Site records yet).
            $existingIndices = collect($client->getIndexes())
                ->map(fn ($idx) => $idx->getUid())
                ->flip();

            $indexUids = ['orders', 'companies', 'users', 'branches', 'sites', 'service_providers', 'container_options', 'waste_streams', 'grades'];

            $queries = collect($indexUids)
                ->filter(fn ($uid) => $existingIndices->has($uid))
                ->map(fn ($uid) => (new SearchQuery)->setIndexUid($uid)->setQuery($query)->setLimit($uid === 'orders' ? 10 : 5))
                ->values()
                ->all();

            if (empty($queries)) {
                return response()->json([]);
            }

            $response = $client->multiSearch($queries);

            $results = [];

            foreach ($response['results'] as $indexResult) {
                $hits = $indexResult['hits'] ?? [];

                if (empty($hits)) {
                    continue;
                }

                $indexUid = $indexResult['indexUid'];
                $results[$indexUid] = array_map(fn ($hit) => $this->mapHit($indexUid, $hit), $hits);
            }

            return response()->json($results);
        } catch (\Throwable) {
            return response()->json([]);
        }
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>
     */
    private function mapHit(string $indexUid, array $hit): array
    {
        return match ($indexUid) {
            'orders' => [
                'id' => $hit['id'],
                'label' => $hit['tracking_number'] ?? "Order #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['company_name'] ?? null, $hit['slip_number'] ?? null, $hit['status'] ?? null])),
                'url' => '/orders/'.$hit['id'],
            ],
            'companies' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Company #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['phone'] ?? null, $hit['registration_number'] ?? null])),
                'url' => '/companies/'.$hit['id'],
            ],
            'users' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "User #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['email'] ?? null, $hit['phone'] ?? null])),
                'url' => '/users/'.$hit['id'].'/edit',
            ],
            'branches' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Branch #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['company_name'] ?? null, $hit['phone'] ?? null])),
                'url' => '/branches/'.$hit['id'],
            ],
            'sites' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Site #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['branch_name'] ?? null, $hit['company_name'] ?? null])),
                'url' => '/collection-points/'.$hit['id'],
            ],
            'service_providers' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Provider #{$hit['id']}",
                'subtitle' => implode(' · ', array_filter([$hit['phone'] ?? null, $hit['registration_number'] ?? null])),
                'url' => '/service-providers/'.$hit['id'],
            ],
            'container_options' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Container #{$hit['id']}",
                'subtitle' => $hit['order_type'] ?? null,
                'url' => '/settings/container-options',
            ],
            'waste_streams' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Stream #{$hit['id']}",
                'subtitle' => $hit['description'] ?? null,
                'url' => '/settings/waste-streams',
            ],
            'grades' => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Grade #{$hit['id']}",
                'subtitle' => $hit['description'] ?? null,
                'url' => '/settings/grades',
            ],
            default => [
                'id' => $hit['id'],
                'label' => $hit['name'] ?? "Item #{$hit['id']}",
                'subtitle' => null,
                'url' => '/',
            ],
        };
    }
}
