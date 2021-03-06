<?php

namespace App\Controller;

use App\Entity\Site;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("admin/site")
 *
 * CRUD Site
 */
class SiteController extends Controller
{
    /**
     * @param SiteRepository $siteRepository
     * @return Response
     *
     * Liste tous les sites
     */
    public function index(SiteRepository $siteRepository): Response
    {
        return $this->render('site/index.html.twig', ['sites' => $siteRepository->findAll()]);
    }

    /**
     * @Route("/new", name="site_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     *
     * Crée un site
     */
    public function new(Request $request): Response
    {
        $site = new Site();
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($site);
            $em->flush();

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('site/new.html.twig', [
            'site' => $site,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="site_show", methods="GET")
     * @param Site $site
     * @return Response
     *
     * Affiche un site
     */
    public function show(Site $site): Response
    {
        return $this->render('site/show.html.twig', ['site' => $site]);
    }

    /**
     * @Route("/{id}/edit", name="site_edit", methods="GET|POST")
     * @param Request $request
     * @param Site $site
     * @return Response
     *
     * Modifie un site
     */
    public function edit(Request $request, Site $site): Response
    {
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('site/edit.html.twig', [
            'site' => $site,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="site_delete", methods="DELETE")
     * @param Request $request
     * @param Site $site
     * @return Response
     *
     * Supprime un site
     */
    public function delete(Request $request, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete'.$site->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($site);
            $em->flush();
        }

        return $this->redirectToRoute('admin_index');
    }
}
