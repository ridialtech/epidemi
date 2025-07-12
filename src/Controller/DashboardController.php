<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Country;
use App\Entity\Zone;
use App\Entity\SurveillancePoint;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CountryRepository;
use App\Repository\ZoneRepository;
use App\Repository\SurveillancePointRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Dashboard and CRUD controller.
 */
class DashboardController extends AbstractController
{
    /** Suggested country names for the autocomplete list. */
    private const COUNTRY_NAMES = [
        'Sénégal', 'Mali', 'Mauritanie', 'Gambie', 'Guinée',
    ];

    /** Example hospital names for point suggestions. */
    private const HOSPITAL_NAMES = [
        // Dakar region
        'Hôpital Principal de Dakar',
        'Hôpital Fann',
        'Hôpital Aristide Le Dantec',
        'Hôpital Dalal Jamm',

        // Thiès region
        'Hôpital régional de Thiès',
        'Hôpital de Mbour',
        'Dispensaire de Tivaouane',
        'Centre de Santé Thiès Nord',

        // Diourbel region
        'Hôpital de Diourbel',
        'Dispensaire de Bambey',
        'Centre de Santé Mbacké',
        'Centre de Santé Ndindy',

        // Fatick region
        'Hôpital de Fatick',
        'Hôpital de Foundiougne',
        'Dispensaire de Gossas',
        'Centre de Santé Fatick Nord',

        // Kaolack region
        'Hôpital de Kaolack',
        'Hôpital de Nioro du Rip',
        'Dispensaire de Guinguinéo',
        'Centre de Santé de Kaolack',

        // Kaffrine region
        'Hôpital de Kaffrine',
        'Dispensaire de Koungheul',
        'Centre de Santé Birkelane',
        'Centre de Santé Malem Hodar',

        // Louga region
        'Hôpital de Louga',
        'Hôpital de Linguère',
        'Dispensaire de Kébémer',
        'Centre de Santé Louga Nord',

        // Saint-Louis region
        'Hôpital de Saint-Louis',
        'Hôpital de Dagana',
        'Hôpital de Podor',
        'Dispensaire de Richard Toll',

        // Matam region
        'Hôpital de Matam',
        'Hôpital de Kanel',
        'Dispensaire de Ranérou',
        'Centre de Santé Matam Ouest',

        // Tambacounda region
        'Hôpital de Tambacounda',
        'Hôpital de Goudiry',
        'Dispensaire de Bakel',
        'Centre de Santé Koumpentoum',

        // Kédougou region
        'Hôpital de Kédougou',
        'Dispensaire de Saraya',
        'Dispensaire de Salemata',
        'Centre de Santé Kédougou Ouest',

        // Sédhiou region
        'Hôpital de Sédhiou',
        'Hôpital de Bounkiling',
        'Dispensaire de Goudomp',
        'Centre de Santé Sédhiou Est',

        // Kolda region
        'Hôpital de Kolda',
        'Hôpital de Vélingara',
        'Dispensaire de Médina Yoro Foulah',
        'Centre de Santé Kolda Sud',

        // Ziguinchor region
        'Hôpital de Ziguinchor',
        'Hôpital de Oussouye',
        'Dispensaire de Bignona',
        'Centre de Santé Ziguinchor Nord'
    ];

    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /** Maximum number of points allowed in a single zone. */
    private const MAX_POINTS_PER_ZONE = 4;
    #[Route('/dashboard', name: 'dashboard')]

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/pays/nouveau', name: 'country_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newCountry(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            if ($name !== '') {
                $country = new Country();
                $country->setName($name);
                $em->persist($country);
                $em->flush();
                return $this->redirectToRoute('country_list');
            }
        }

        return $this->render('admin/country_new.html.twig', [
            'suggestions' => self::COUNTRY_NAMES,
        ]);
    }

    #[Route('/pays', name: 'country_list')]
    public function countryList(CountryRepository $repo): Response
    {
        return $this->render('admin/country_list.html.twig', [
            'countries' => $repo->findAllOrdered(),
        ]);
    }

    #[Route('/pays/{id}/modifier', name: 'country_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editCountry(Country $country, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            if ($name !== '') {
                $country->setName($name);
                $em->flush();
                return $this->redirectToRoute('country_list');
            }
        }

        return $this->render('admin/country_edit.html.twig', [
            'country' => $country,
            'suggestions' => self::COUNTRY_NAMES,
        ]);
    }

    #[Route('/pays/{id}/supprimer', name: 'country_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deleteCountry(Country $country, EntityManagerInterface $em): Response
    {
        $em->remove($country);
        $em->flush();
        return $this->redirectToRoute('country_list');
    }

    #[Route('/zone/nouvelle', name: 'zone_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newZone(Request $request, EntityManagerInterface $em, CountryRepository $countries): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $countryId = $request->request->get('country');
            if ($name !== '' && $countryId) {
                $country = $countries->find($countryId);
                if ($country) {
                    $zone = new Zone();
                    $zone->setName($name);
                    $zone->setCountry($country);
                    $em->persist($zone);
                    $this->updateZoneStats($zone);
                    $em->flush();
                    return $this->redirectToRoute('zone_list');
                }
            }
        }

        return $this->render('admin/zone_new.html.twig', [
            'countries' => $countries->findAllOrdered(),
        ]);
    }

    #[Route('/zone/{id}/modifier', name: 'zone_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editZone(Zone $zone, Request $request, EntityManagerInterface $em, CountryRepository $countries): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $countryId = $request->request->get('country');
            if ($name !== '' && $countryId) {
                $country = $countries->find($countryId);
                if ($country) {
                    $zone->setName($name);
                    $zone->setCountry($country);
                    $this->updateZoneStats($zone);
                    $em->flush();
                    return $this->redirectToRoute('zone_list');
                }
            }
        }

        return $this->render('admin/zone_edit.html.twig', [
            'zone' => $zone,
            'countries' => $countries->findAllOrdered(),
        ]);
    }

    #[Route('/zone/{id}/supprimer', name: 'zone_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deleteZone(Zone $zone, EntityManagerInterface $em): Response
    {
        $em->remove($zone);
        $em->flush();
        return $this->redirectToRoute('zone_list');
    }

    #[Route('/point/nouveau', name: 'point_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function newPoint(Request $request, EntityManagerInterface $em, ZoneRepository $zones): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $zoneId = $request->request->get('zone');
            if ($name !== '' && $zoneId) {
                $zone = $zones->find($zoneId);
                if ($zone) {
                    if ($zone->getPoints()->count() >= self::MAX_POINTS_PER_ZONE) {
                        $this->addFlash('error', 'Cette zone possède déjà ' . self::MAX_POINTS_PER_ZONE . ' points de surveillance.');
                        return $this->redirectToRoute('point_list');
                    }
                    $point = new SurveillancePoint();
                    $point->setName($name);
                    $zone->addPoint($point);
                    $population = (int)$request->request->get('population', 0);
                    $symptomatic = (int)$request->request->get('symptomatic', 0);
                    $positive = (int)$request->request->get('positive', 0);
                    $point->setPopulation($population);
                    $point->setSymptomatic($symptomatic);
                    $point->setPositive($positive);
                    $em->persist($point);
                    $this->updateZoneStats($zone);
                    $em->flush();
                    return $this->redirectToRoute('point_list');
                }
            }
        }

        return $this->render('admin/point_new.html.twig', [
            'zones' => $zones->findAll(),
            'suggestions' => self::HOSPITAL_NAMES,
        ]);
    }

    #[Route('/point/{id}/modifier', name: 'point_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function editPoint(SurveillancePoint $point, Request $request, EntityManagerInterface $em, ZoneRepository $zones): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $zoneId = $request->request->get('zone');
            if ($name !== '' && $zoneId) {
                $zone = $zones->find($zoneId);
                if ($zone) {
                    $oldZone = $point->getZone();
                    if ($zone !== $oldZone && $zone->getPoints()->count() >= self::MAX_POINTS_PER_ZONE) {
                        $this->addFlash('error', 'Cette zone possède déjà ' . self::MAX_POINTS_PER_ZONE . ' points de surveillance.');
                        return $this->redirectToRoute('point_list');
                    }
                    $point->setName($name);
                    if ($zone !== $oldZone) {
                        $oldZone->removePoint($point);
                        $zone->addPoint($point);
                    }
                    $population = (int)$request->request->get('population', 0);
                    $symptomatic = (int)$request->request->get('symptomatic', 0);
                    $positive = (int)$request->request->get('positive', 0);
                    $point->setPopulation($population);
                    $point->setSymptomatic($symptomatic);
                    $point->setPositive($positive);
                    $this->updateZoneStats($oldZone);
                    if ($oldZone !== $zone) {
                        $this->updateZoneStats($zone);
                    }
                    $em->flush();
                    return $this->redirectToRoute('point_list');
                }
            }
        }

        return $this->render('admin/point_edit.html.twig', [
            'point' => $point,
            'zones' => $zones->findAll(),
            'suggestions' => self::HOSPITAL_NAMES,
        ]);
    }

    #[Route('/point/{id}/supprimer', name: 'point_delete', methods: ['POST'])]
    #[IsGranted('ROLE_AGENT')]
    public function deletePoint(SurveillancePoint $point, EntityManagerInterface $em): Response
    {
        $zone = $point->getZone();
        $zone->removePoint($point);
        $em->remove($point);
        $this->updateZoneStats($zone);
        $em->flush();
        return $this->redirectToRoute('point_list');
    }

    #[Route('/points', name: 'point_list')]
    public function pointList(SurveillancePointRepository $repo): Response
    {
        return $this->render('admin/point_list.html.twig', [
            'points' => $repo->findAll(),
        ]);
    }

    #[Route('/zones', name: 'zone_list')]
    public function zoneList(ZoneRepository $repo): Response
    {
        return $this->render('admin/zone_list.html.twig', [
            'zones' => $repo->findAll(),
        ]);
    }

    #[Route('/zones/critiques', name: 'critical_zones')]
    public function criticalZones(ZoneRepository $repo): Response
    {
        return $this->render('admin/critical_zones.html.twig', [
            // only zones in red status are considered critical
            'zones' => $repo->findBy(['status' => 'rouge']),
        ]);
    }

    #[Route('/carte', name: 'view_map')]
    public function viewMap(ZoneRepository $repo, SurveillancePointRepository $points): Response
    {
        $apiKey = $this->getParameter('google_maps_api_key');

        $zones = [];
        foreach ($repo->findAll() as $zone) {
            $zones[] = [
                'name' => $zone->getName(),
                'status' => $zone->getStatus(),
                'population' => $zone->getPopulation(),
                'symptomatic' => $zone->getSymptomatic(),
                'positive' => $zone->getPositive(),

            ];
        }

        $pts = [];
        foreach ($points->findAll() as $point) {
            $pts[] = [
                'name' => $point->getName(),
                'zone' => $point->getZone()->getName(),
                'population' => $point->getPopulation(),
                'symptomatic' => $point->getSymptomatic(),
                'positive' => $point->getPositive(),
            ];
        }

        return $this->render('admin/view_map.html.twig', [
            'apiKey' => $apiKey,
            'zones' => $zones,
            'points' => $pts,
        ]);
    }

    private function updateZoneStats(Zone $zone): void
    {
        $population = 0;
        $symptomatic = 0;
        $positive = 0;

        $previousStatus = $zone->getStatus();

        foreach ($zone->getPoints() as $p) {
            $population += $p->getPopulation() ?? 0;
            $symptomatic += $p->getSymptomatic() ?? 0;
            $positive += $p->getPositive() ?? 0;
        }

        $zone->setPopulation($population);
        $zone->setSymptomatic($symptomatic);
        $zone->setPositive($positive);
        $newStatus = $this->calculateStatus($population, $symptomatic, $positive);
        $zone->setStatus($newStatus);

        if ($newStatus === 'rouge' && $previousStatus !== 'rouge') {
            $this->sendRedZoneEmail($zone);
        }
    }

    private function sendRedZoneEmail(Zone $zone): void
    {
        $lines = [];
        $lines[] = sprintf('La zone de nom %s est rouge.', $zone->getName());
        $lines[] = sprintf('Nombre d\'habitants: %d', $zone->getPopulation());
        $lines[] = sprintf('Nombre de points de surveillance: %d', $zone->getPoints()->count());
        $lines[] = 'Liste des points :';
        foreach ($zone->getPoints() as $p) {
            $lines[] = sprintf(
                '- %s : habitants=%d, symptomatiques=%d, confirmés=%d',
                $p->getName(),
                $p->getPopulation(),
                $p->getSymptomatic(),
                $p->getPositive()
            );
        }

        $email = (new Email())
            ->from('noreply@example.com')
            ->to('fayeibracheikh@gmail.com')
            ->subject('Zone rouge : ' . $zone->getName())
            ->text(implode("\n", $lines));

        $this->mailer->send($email);
    }

    private function calculateStatus(int $population, int $symptomatic, int $positive): string
    {
        if ($population <= 0) {
            return 'verte';
        }

        $rate = $positive / $population * 100;

        if ($rate >= 15) {
            return 'rouge';
        }

        if ($rate >= 5) {
            return 'orange';
        }

        return 'verte';
    }
}
