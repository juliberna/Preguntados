<?php

require_once 'vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Exception;

class PdfGenerator
{
    private $dompdf;
    public function __construct() {
        $this->dompdf = new Dompdf();
    }

    public function generarPdf($html, $nombreArchivo = "documento.pdf", $descargar = false) {
        try {
            $this->dompdf->loadHtml($html);

            $this->dompdf->setPaper('A4', 'portrait');

            $this->dompdf->render();

            $this->dompdf->stream($nombreArchivo, ["Attachment" => $descargar]);

            exit;
        } catch (Exception $e) {
            error_log("PdfGenerator error: " . $e->getMessage());
            throw new \RuntimeException("No se pudo generar el PDF.");
        }
    }

}