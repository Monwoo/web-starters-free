<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace App\Services;

use TCPDF;
use TCPDF_FONTS;

class MwsTCPDF extends TCPDF
{
  protected $mwsFontname;
  // https://tcpdf.org/files/examples/example_003.phps
	public function __construct(
    $orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false
  ) {
    parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

    // K_PATH_FONTS constant ? Below will not work :
    // $this->mwsFontname = TCPDF_FONTS::addTTFfont('src/ressources/fonts/arial-cufonfonts-com/arialceb.ttf', 'TrueTypeUnicode', '', 32);
    // $this->setFont('arialceb', '', 14, '', false);

    $this->setHeaderMargin(-2);
  }

  public function Header()
  {
    // $this->SetFont('helvetica', '', 8);
    // parent::Header();

    // https://stackoverflow.com/questions/25975074/write-html-to-custom-header-tcpdf
    // $headerData = $this->getHeaderData();
    // $this->SetFont('helvetica', 'B', 10);
    // $this->writeHTML($headerData['string']);

    // apps/mws-sf-pdf-billings/backend/vendor/tecnickcom/tcpdf/tcpdf.php
    // if ($this->header_xobjid === false) {
		// 	// start a new XObject Template
		// 	$this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
		// 	$headerfont = $this->getHeaderFont();
		// 	$headerdata = $this->getHeaderData();
		// 	$this->y = $this->header_margin;
		// 	if ($this->rtl) {
		// 		$this->x = $this->w - $this->original_rMargin;
		// 	} else {
		// 		$this->x = $this->original_lMargin;
		// 	}
		// 	if (($headerdata['logo']) AND ($headerdata['logo'] != K_BLANK_IMAGE)) {
		// 		$imgtype = TCPDF_IMAGES::getImageFileType(K_PATH_IMAGES.$headerdata['logo']);
		// 		if (($imgtype == 'eps') OR ($imgtype == 'ai')) {
		// 			$this->ImageEps(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
		// 		} elseif ($imgtype == 'svg') {
		// 			$this->ImageSVG(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
		// 		} else {
		// 			$this->Image(K_PATH_IMAGES.$headerdata['logo'], '', '', $headerdata['logo_width']);
		// 		}
		// 		$imgy = $this->getImageRBY();
		// 	} else {
		// 		$imgy = $this->y;
		// 	}
		// 	$cell_height = $this->getCellHeight($headerfont[2] / $this->k);
		// 	// set starting margin for text data cell
		// 	if ($this->getRTL()) {
		// 		$header_x = $this->original_rMargin + ($headerdata['logo_width'] * 1.1);
		// 	} else {
		// 		$header_x = $this->original_lMargin + ($headerdata['logo_width'] * 1.1);
		// 	}
		// 	$cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.1);
		// 	$this->setTextColorArray($this->header_text_color);
		// 	// header title
		// 	$this->setFont($headerfont[0], 'B', $headerfont[2] + 1);
		// 	$this->setX($header_x);
		// 	$this->Cell($cw, $cell_height, $headerdata['title'], 0, 1, '', 0, '', 0);
		// 	// header string
		// 	$this->setFont($headerfont[0], $headerfont[1], $headerfont[2]);
		// 	$this->setX($header_x);
		// 	$this->MultiCell($cw, $cell_height, $headerdata['string'], 0, '', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
		// 	// print an ending header line
		// 	$this->setLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $headerdata['line_color']));
		// 	$this->setY((2.835 / $this->k) + max($imgy, $this->y));
		// 	if ($this->rtl) {
		// 		$this->setX($this->original_rMargin);
		// 	} else {
		// 		$this->setX($this->original_lMargin);
		// 	}
		// 	$this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
		// 	$this->endTemplate();
		// }
		// // print header template
		// $x = 0;
		// $dx = 0;
		// if (!$this->header_xobj_autoreset AND $this->booklet AND (($this->page % 2) == 0)) {
		// 	// adjust margins for booklet mode
		// 	$dx = ($this->original_lMargin - $this->original_rMargin);
		// }
		// if ($this->rtl) {
		// 	$x = $this->w + $dx;
		// } else {
		// 	$x = 0 + $dx;
		// }
		// $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
		// if ($this->header_xobj_autoreset) {
		// 	// reset header xobject template at each page
		// 	$this->header_xobjid = false;
		// }

    // https://stackoverflow.com/questions/29896102/how-do-you-add-custom-fonts-in-tcpdf
    // $pdf->SetFont('rumpelstiltskinwebfont', '', 14, '', false);
    // $pdf->Write(0, 'Fill text', '', 0, '', true, 0, false, false, 0);

    // $this->setHeaderFont(['freemono', '', 10]);
    $this->setHeaderFont(['freesans', '', 12]);
    // $this->setHeaderFont(['freeserif', '', 12]);
    parent::Header();
  }
}
