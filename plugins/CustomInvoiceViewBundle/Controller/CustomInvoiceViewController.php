<?php

namespace KimaiPlugin\CustomInvoiceViewBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[IsGranted('ROLE_ADMIN')]
class CustomInvoiceViewController
{
    private Environment $twig;
    private EntityManagerInterface $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    public function index(Request $request): Response
    {
        $year = $request->query->get('year', date('Y'));
        $data = $this->getInvoiceData((int)$year);

        $content = $this->twig->render('@CustomInvoiceView/invoice-overview.html.twig', [
            'currentYear' => $year,
            'data' => $data,
        ]);

        return new Response($content);
    }

    public function data(Request $request): JsonResponse
    {
        $year = $request->query->get('year', date('Y'));
        $data = $this->getInvoiceData((int)$year);

        return new JsonResponse($data);
    }

    private function getInvoiceData(int $year): array
    {
        // Query invoices for the specified year
        $connection = $this->entityManager->getConnection();

        // Get all invoices for the year
        $sql = "
            SELECT
                id,
                invoice_number,
                customer_id,
                DATE_FORMAT(created_at, '%Y-%m') as month,
                DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                total,
                currency,
                status
            FROM kimai2_invoices
            WHERE YEAR(created_at) = :year
            ORDER BY created_at DESC
        ";

        $stmt = $connection->prepare($sql);
        $stmt->bindValue('year', $year);
        $result = $stmt->executeQuery();
        $invoices = $result->fetchAllAssociative();

        // Get customer names
        $customerIds = array_unique(array_column($invoices, 'customer_id'));
        $customers = [];
        if (!empty($customerIds)) {
            $customerSql = "SELECT id, name, company FROM kimai2_customers WHERE id IN (" . implode(',', $customerIds) . ")";
            $customerResult = $connection->executeQuery($customerSql);
            foreach ($customerResult->fetchAllAssociative() as $customer) {
                $customers[$customer['id']] = $customer['company'] ?: $customer['name'];
            }
        }

        // Initialize monthly data (all 12 months)
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%d-%02d', $year, $month);
            $monthlyData[$monthKey] = [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'paid' => 0,
                'pending' => 0,
            ];
        }

        // Calculate totals
        $totalPaid = 0;
        $totalPending = 0;

        // Process invoices
        $invoiceList = [];
        foreach ($invoices as $invoice) {
            $amount = (float)$invoice['total'];
            $status = $invoice['status'];
            $month = $invoice['month'];

            // Add customer name
            $customerName = $customers[$invoice['customer_id']] ?? 'Unknown';

            // Add to monthly data
            if (isset($monthlyData[$month])) {
                if ($status === 'paid') {
                    $monthlyData[$month]['paid'] += $amount;
                    $totalPaid += $amount;
                } else {
                    // pending, new, or any other status counts as pending
                    $monthlyData[$month]['pending'] += $amount;
                    $totalPending += $amount;
                }
            }

            // Add to invoice list
            $invoiceList[] = [
                'id' => $invoice['id'],
                'number' => $invoice['invoice_number'],
                'date' => $invoice['date'],
                'customer' => $customerName,
                'amount' => $amount,
                'currency' => $invoice['currency'],
                'status' => $status,
            ];
        }

        return [
            'year' => $year,
            'monthlyData' => array_values($monthlyData),
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'invoices' => $invoiceList,
            'currency' => $invoices[0]['currency'] ?? 'USD',
        ];
    }
}
