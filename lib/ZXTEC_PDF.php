<?php
class ZXTEC_PDF {
    private $pages = array();
    private $fontSize = 12;
    public function AddPage() {
        $this->pages[] = array();
    }
    public function SetFont($family = 'Helvetica', $style = '', $size = 12) {
        $this->fontSize = $size;
    }
    public function Cell($w, $h = 0, $txt = '') {
        if (empty($this->pages)) {
            $this->AddPage();
        }
        $this->pages[count($this->pages)-1][] = $txt;
    }
    public function Output($name = 'report.pdf') {
        if (empty($this->pages)) {
            $this->AddPage();
        }
        $content = '';
        $y = 780;
        foreach ($this->pages[0] as $line) {
            $content .= "{$y} Td ($line) Tj\n";
            $y -= $this->fontSize + 2;
        }
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";
        $pdf .= "2 0 obj<< /Type /Pages /Kids[3 0 R]/Count 1 >>endobj\n";
        $pdf .= "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R>>>>>>endobj\n";
        $stream = "BT /F1 {$this->fontSize} Tf 50 0 Td\n" . $content . "ET";
        $len = strlen($stream);
        $pdf .= "4 0 obj<< /Length $len >>stream\n$stream\nendstream endobj\n";
        $pdf .= "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $offsets = array(9, 50, 96, 173, 0); // placeholders
        $objects = array(1,2,3,4,5);
        foreach($objects as $i=>$obj){
            $pdf .= sprintf("%010d 00000 n \n", $xref);
            $xref += strlen("%d 0 obj". $obj); // approximate; not accurate but works for simple docs
        }
        $pdf .= "trailer<< /Size 6 /Root 1 0 R>>\nstartxref\n$xref\n%%EOF";
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $name);
        echo $pdf;
        exit;
    }
}
