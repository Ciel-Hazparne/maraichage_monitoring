<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TabController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $httpClient) {}

    #[Route('/tab', name: 'tab')]
    public function index(): Response
    {
        $mesures = [];
        $baseUrl = 'http://localhost:8000/api/mesures?';
        $libelles = [
            'humidite_champ',
            'humidite_serre',
            'temp_champ',
            'temp_serre',
        ];

        foreach ($libelles as $libelle) {
            $url = $baseUrl . http_build_query([
                    'libelleMesure.libelle' => $libelle,
                ]);

            try {
                $response = $this->httpClient->request('GET', $url);
                $data = $response->toArray()['member'] ?? [];
                $mesures = array_merge($mesures, $data);
            } catch (\Throwable $e) {
                $this->addFlash('error', "Erreur pour $libelle : " . $e->getMessage());
            }
        }

        return $this->render('monitoring/tab.html.twig', [
            'current_menu' => 'tab',
            'mesures' => $mesures,
            'tableConfigs' => [
                ['title' => 'Humidité Serre (%)', 'libelle' => 'humidite_serre'],
                ['title' => 'Température Serre (°C)', 'libelle' => 'temp_serre'],
                ['title' => 'Humidité Champ (%)', 'libelle' => 'humidite_champ'],
                ['title' => 'Température Champ (°C)', 'libelle' => 'temp_champ'],
            ],
        ]);
    }

    #[Route('/token', name: 'api_token', methods: ['POST'])]
    public function getToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $response = $this->httpClient->request('POST', 'http://localhost:8000/api/auth_token', [
                'json' => [
                    'email' => $data['email'] ?? '',
                    'password' => $data['password'] ?? '',
                ],
            ]);

            return new JsonResponse($response->toArray(), $response->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Erreur de connexion'], 500);
        }
    }

    #[Route('/mesure/new', name: 'mesure_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        $data = json_decode($request->getContent(), true);

        try {
            $res = $this->httpClient->request('POST', 'http://localhost:8000/api/mesures', [
                'headers' => [
                    'Authorization' => $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'libelleMesure' => $data['libelle'] ?? '',
                    'valeur' => (float)($data['valeur'] ?? 0),
                ]
            ]);

            return new JsonResponse($res->toArray(), $res->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Erreur lors de la création'], 500);
        }
    }

    #[Route('/mesure/edit/{id}', name: 'mesure_edit', methods: ['PATCH'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        $data = json_decode($request->getContent(), true);

        try {
            $res = $this->httpClient->request('PATCH', "http://localhost:8000/api/mesures/$id", [
                'headers' => [
                    'Authorization' => $token,
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'body' => json_encode(['valeur' => (float)($data['valeur'] ?? 0)]),
            ]);

            return new JsonResponse(null, $res->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Erreur lors de la modification'], 500);
        }
    }

    #[Route('/mesure/delete/{id}', name: 'mesure_delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');

        try {
            $res = $this->httpClient->request('DELETE', "http://localhost:8000/api/mesures/$id", [
                'headers' => [
                    'Authorization' => $token,
                ],
            ]);

            return new JsonResponse(null, $res->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Erreur lors de la suppression'], 500);
        }
    }
}
