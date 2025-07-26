<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class MesureGraphController extends AbstractController
{
    #[Route('/graph', name: 'graph')]
    public function index(ChartBuilderInterface $chartBuilder, HttpClientInterface $httpClient): Response
    {
        $baseUrl = 'http://localhost:8000/api/mesures?';
        $libelles = [
            'humidite_champ',
            'humidite_serre',
            'temp_champ',
            'temp_serre',
        ];

        $charts = [];

        foreach ($libelles as $libelle) {
            $url = $baseUrl . http_build_query([
                    'libelleMesure.libelle' => $libelle,
                    'itemsPerPage' => 30,
                ]);

            try {
                $response = $httpClient->request('GET', $url);
                $data = $response->toArray()['member'];
            } catch (\Throwable $e) {
                $this->addFlash('error', "Erreur de récupération des données pour $libelle : " . $e->getMessage());
                $data = [];
            }

            $labels = [];
            $values = [];

            foreach ($data as $mesure) {
                $labels[] = date('d/m/Y H:i', strtotime($mesure['createdAt']));
                $values[] = $mesure['valeur'];
            }

            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
            $chart->setData([
                'labels' => $labels,
                'datasets' => [[
                    'label' => $libelle,
                    'backgroundColor' => 'rgb(100, 155, 125)',
                    'borderColor' => 'rgb(24, 188, 156)',
                    'data' => $values,
                ]],
            ]);

            $chart->setOptions([
                'scales' => [
                    'y' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
            ]);

            $charts[$libelle] = $chart;
        }

        return $this->render('mesure/graph.html.twig', [
            'current_menu' => 'graph',
            'chart_humidite_champ' => $charts['humidite_champ'] ?? null,
            'chart_temp_champ' => $charts['temp_champ'] ?? null,
            'chart_humidite_serre' => $charts['humidite_serre'] ?? null,
            'chart_temp_serre' => $charts['temp_serre'] ?? null,]);
    }
}
