<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Form\CampaignType;
use App\Repository\CampaignRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/campaign")
 *
 * CRUD campagne de mailing
 */
class CampaignController extends Controller
{
    /**
     * @Route("/", name="campaign_index", methods="GET")
     *
     * Liste toutes les campagnes de mailing
     */
    public function index(CampaignRepository $campaignRepository): Response
    {
        return $this->render('campaign/index.html.twig', ['campaigns' => $campaignRepository->findAll()]);
    }

    /**
     * @Route("/new", name="campaign_new", methods="GET|POST")
     *
     * Crée un nouvelle campagne et envoie un mail aux consultants concernés selon :
     *      - soit un template existant
     *      - soit un template personnalisé créé via le formulaire de création de campagne.
     */
    public function new(Request $request, \Swift_Mailer $mailer): Response
    {
        $campaign = new Campaign();
        $mailingLists = $this->getDoctrine()->getRepository('App:MailingList')->findAll();
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $campaign->setSendDate(new \DateTime("now"));
            $em = $this->getDoctrine()->getManager();
            $em->persist($campaign);
            $em->flush();

            foreach ($campaign->getUsers() as $user){
                if($form["template"]->getData() !== null){
                    $message = (new \Swift_Message($form['name']->getData()))
                        ->setFrom('laurie.guibert@consultants-solutec.fr')
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                'emails/' . str_replace(' ', '_', $form["template"]->getData()->getName() . '.html.twig'),
                                array('user' => $user)
                            ),
                            'text/html'
                        )
                    ;
                } else {
                    $message = (new \Swift_Message($form['name']->getData()))
                        ->setFrom('laurie.guibert@consultants-solutec.fr')
                        ->setTo($user->getEmail())
                        ->setBody($form['newTemplate']->getData(),
                            'text/html'
                        )
                        ->attach(\Swift_Attachment::fromPath($this->get('kernel')->getProjectDir() . '/public/files/campaigns/' . $campaign->getFile())) ;
                    ;
                }

                $mailer->send($message);
            }

            return $this->redirectToRoute('campaign_index');
        }

        return $this->render('campaign/new.html.twig', [
            'campaign' => $campaign,
            'form' => $form->createView(),
            'mailingLists' => $mailingLists
        ]);
    }

    /**
     * @Route("/{id}", name="campaign_show", methods="GET")
     *
     * Affiche une campagne de mailing
     */
    public function show(Campaign $campaign): Response
    {
        return $this->render('campaign/show.html.twig', ['campaign' => $campaign]);
    }

    /**
     * @Route("/{id}/edit", name="campaign_edit", methods="GET|POST")
     *
     * Modifie une campagne de mailing
     */
    public function edit(Request $request, Campaign $campaign): Response
    {
        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('campaign_edit', ['id' => $campaign->getId()]);
        }

        return $this->render('campaign/edit.html.twig', [
            'campaign' => $campaign,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="campaign_delete", methods="DELETE")
     *
     * Supprime une campagne de mailing
     */
    public function delete(Request $request, Campaign $campaign): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campaign->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($campaign);
            $em->flush();
        }

        return $this->redirectToRoute('campaign_index');
    }

    /**
     * @Route("/category/template", name="category_template", methods="GET")
     *
     * Récupère les templates de mails liés à la catégorie de la campagne
     * Requête ajax
     */
    public function listTemplateOfCategoryAction(Request $request)
    {
        // Get Entity manager and repository
        $em = $this->getDoctrine()->getManager();
        $templatesRepository = $em->getRepository("App\Entity\Template");

        $templates = $templatesRepository->createQueryBuilder("q")
            ->where("q.category = :category_id")
            ->setParameter("category_id", $request->query->get("category_id"))
            ->getQuery()
            ->getResult();

        $responseArray = array();
        foreach($templates as $template){
            $responseArray[] = array(
                "id" => $template->getId(),
                "name" => $template->getName(),
                "body" => $template->getBody()
            );
        }

        return new JsonResponse($responseArray);
    }
}
