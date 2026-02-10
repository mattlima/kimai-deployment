<?php

namespace KimaiPlugin\DBExportBundle\Controller;

use KimaiPlugin\DBExportBundle\Service\DatabaseDumpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[Route(path: '/db-export')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[IsGranted('system_configuration')]
class DBExportController extends AbstractController
{
    private Environment $twig;
    private DatabaseDumpService $dumpService;

    public function __construct(Environment $twig, DatabaseDumpService $dumpService)
    {
        $this->twig = $twig;
        $this->dumpService = $dumpService;
    }

    /**
     * Display the database export page.
     */
    #[Route(path: '', name: 'db_export_page', methods: ['GET'])]
    public function index(): Response
    {
        return new Response($this->twig->render('@DBExport/export.html.twig'));
    }

    /**
     * Generate and download the database dump.
     */
    #[Route(path: '/download', name: 'db_export_download', methods: ['POST'])]
    public function download(): Response
    {
        try {
            // Create the database dump
            $dumpFilePath = $this->dumpService->createDump();
            
            // Create response with the dump file
            $response = new BinaryFileResponse($dumpFilePath);
            
            // Set headers to trigger download
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $this->dumpService->getExportFilename()
            );
            
            // Delete the temp file after sending
            $response->deleteFileAfterSend(true);
            
            return $response;
        } catch (\Exception $e) {
            // Return error page
            return new Response(
                $this->twig->render('@DBExport/export.html.twig', [
                    'error' => 'Failed to create database export: ' . $e->getMessage(),
                ]),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
