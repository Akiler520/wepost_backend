<?php
/**
 *	file convert
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2010-2013
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2013-02-19
 */
class AkFileConvert
{
	/**
	 * exec output
	 *
	 * @var unknown_type
	 */
	private $_output = array();

	/**
	 * the os support of the convert
	 *
	 * @var string
	 */
	private $_supportOS = 'WIN';

	/**
	 * the path of the files
	 *
	 * @var string
	 */
	private $_filePath = UPLOAD_PATH;

	function __construct() {
		$this->_output = array();
	}

	function setFilePath($path) {
		if (!is_dir($path)) {
			return false;
		}

		$this->_filePath = $path;

		return true;
	}

	function isSupport() {
		if (stripos(PHP_OS, $this->_supportOS) !== false) {
			return true;
		}

		return false;
	}

	function getContents($fileBaseName){
		$contents = array();
		$contents['text'] = "";
		$contents['pages'] = "0";
		$fileInfo = pathinfo($fileBaseName);
		$extension = strtolower($fileInfo['extension']);
		$filename = strtolower($fileInfo['filename']);
		
		$supportExtGetContents = array('htm', 'html');		//only user file_get_contents to get contents;

		if ($extension == "pdf") {
			$textFile = CONVERT_PATH.$filename.".txt";
			$cmd = CONVERT_PDF2TEXT.' -q -raw -htmlmeta '.$this->_filePath.$fileBaseName.' '.$textFile;

			exec($cmd.' 2>&1', $this->_output);

			$contents['text'] = trim(@file_get_contents($textFile));
			$contents['pages'] = "0";

			@unlink($textFile);
		}

		elseif ($extension == "doc") {
			$cmd = CONVERT_ANTIWORD." -t -m UTF-8 ".$this->_filePath.$fileBaseName;

			$contents['text'] = trim(shell_exec($cmd));
		}

		elseif ($extension == "tif") {
			$contents = $this->getOCR($fileBaseName);
		}

		elseif ($extension == "odt") {
			$contents['text'] =  $this->odt2text($this->_filePath.$fileBaseName);
		}

		elseif ($extension == "ods") {
			$contents['text'] =  $this->ods2text($this->_filePath.$fileBaseName);
		}

		elseif ($extension == "docx") {
			$tmp = $this->docx2text($this->_filePath.$fileBaseName);
			//			$tmp = AkString::changeCharset($tmp);
			$contents['text'] = $tmp;
		}

		elseif ($extension == 'txt' || $extension == 'xml') {
			$tmp = @file_get_contents($this->_filePath.$fileBaseName);
			$tmp = AkString::changeCharset($tmp);

			$contents['text'] = $tmp;
		}

		elseif ($extension == 'eml') {
			$contents['text'] = @file_get_contents($this->_filePath.$fileBaseName);
		}

		elseif ($extension == 'xls') {
			$excelObj = new Excel();
			$contents['text'] = $excelObj->getXLSContent($filename);
		}

		elseif ($extension == "xlsx") {
			$contents['text'] = $this->xlsx2text($this->_filePath.$fileBaseName);
		}

		elseif ($extension == "pptx") {
			$contents['text'] = $this->pptx2text($this->_filePath.$fileBaseName);
		}
		
		elseif (in_array($extension, $supportExtGetContents)) {
			$contents['text'] = @file_get_contents($this->_filePath.$fileBaseName);
		}
		
		else {
			//$tmp = @file_get_contents($this->_filePath.$fileBaseName);
			$contents['text'] = '';//$tmp;
		}
		
		if ($extension != 'eml') {
			$contents['text'] = htmlspecialchars($contents['text']);
		}

		return $contents;
	}

	/**
	 * get the contents of file with tif extension
	 *
	 * @param string $fileBaseName
	 * @return array
	 */
	function getOCR($fileBaseName){
		$tmp = pathinfo($fileBaseName);
		$filename = $tmp['filename'];
		$extension = $tmp['extension'];

		$convertFile = $this->_filePath.$fileBaseName;
		$convertToFile = CONVERT_PATH.$filename;	// not contains extension

		$contents['pages'] = 0;
		$contents['text'] = "";

		$cmd = CONVERT_TESSERACT.$convertFile." ".$convertToFile;

		exec($cmd.' 2>&1', $this->_output);

		$contents['text'] = trim(@file_get_contents($convertToFile.".txt"));

		@unlink($convertToFile.".txt");

		return $contents;
	}

	/**
	 * convert odt to text
	 * $filename must contains file path.
	 *
	 * @param string $filename
	 * @return string
	 */
	function odt2text($filename) {
		return $this->readZippedXML($filename, "content.xml");
	}

	function ods2text($filename) {
		return $this->readZippedXML($filename, "content.xml");
	}

	function docx2text($filename) {
		return $this->readZippedXML($filename, "word/document.xml");
	}

	function pptx2text($filename) {
		return $this->readZippedXML($filename, "ppt/slides");
	}

	function xlsx2text($filename) {
		return $this->readZippedXML($filename, "xl/sharedStrings.xml");
	}

	/**
	 * get contents of zip file
	 *
	 * @param string $archiveFile	file path
	 * @param string $dataFile		data of file key
	 * @return string or false
	 */
	function readZippedXML($archiveFile, $dataFile) {
		// Create new ZIP archive
		$zip = new ZipArchive;

		// Open received archive file
		if (true === $zip->open($archiveFile)) {
			// If done, search for the data file in the archive
			if (($index = $zip->locateName($dataFile)) !== false) {
				// If found, read it to the string
				$data = $zip->getFromIndex($index);
				// Close archive file
				$zip->close();
				// Load XML from a string
				// Skip errors and warnings
				$xml = DOMDocument::loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
				// Return data without XML formatting tags
				return strip_tags($xml->saveXML());
			}
			$zip->close();
		}

		// In case of failure return false
		return false;
	}

	/**
	 * use openoffice to convert file to pdf
	 * Notice: must install openoffice in the os first.
	 *
	 * @param string $name
	 * @param string $value
	 * @param object $osm
	 * @return array
	 */
	function MakePropertyValue($name, $value, $osm){
		$oStruct = $osm->Bridge_GetStruct("com.sun.star.beans.PropertyValue");
		$oStruct->Name = $name;
		$oStruct->Value = $value;

		return $oStruct;
	}

	/**
	 * use openoffice to convert file to pdf
	 *
	 * @param string $doc_url
	 * @param string $output_url
	 */
	function office2pdf($doc_url, $output_url){
		try {
			/*$cmd = CONVERT_OPENOFFICE_SERVICE;
			exec($cmd.' 2>&1', $this->_output);

			if (!$this->isSuccess()) {
			return false;
			}*/

			$osm = new COM("com.sun.star.ServiceManager");// or die ("Please be sure that OpenOffice.org is installed.\n");
		} catch (Exception $e) {
			AkString::printm(AkString::changeCharset($e->getMessage()));
		}

		$args = array($this->MakePropertyValue("Hidden", true, $osm));
		$oDesktop = $osm->createInstance("com.sun.star.frame.Desktop");
		$oWriterDoc = $oDesktop->loadComponentFromURL($doc_url, "_blank", 0, $args);
		$export_args = array($this->MakePropertyValue("FilterName", "writer_pdf_Export", $osm));
		$oWriterDoc->storeToURL($output_url, $export_args);

		$oWriterDoc->close(true);

		//		$osm->dispose();

		return true;
	}

	function pyOffice2pdf($doc_url, $output_url){
		$cmd = sprintf('C:\\Python33\\python.exe "C:\\Program Files (x86)\\OpenOffice.org 3\\program\\DocumentConverter.py" %s %s 2>&1', $doc_url, $output_url);

		exec($cmd, $this->_output);
		AkString::printm($this->_output);
		if (!$this->isSuccess()) {
			return false;
		}

		return true;
	}

	function openOfficeDocumentConvert($input, $output){
		$cmd = sprintf(CONVERT_OPENOFFICE_JAVA, $input, $output);
		exec($cmd, $this->_output);

		return $this->isSuccess();
	}

	/**
	 * convert html to pdf
	 *
	 * @param string $doc_src	path to source file, include filename
	 * @param string $doc_dst	path to output file, include filename
	 */
	function html2pdf($doc_src, $doc_dst) {
		require_once(LIB_PATH.'html2fpdf/html2fpdf.php');

		$strContent = @file_get_contents($doc_src);

		$strContent = iconv('utf-8', 'gbk', $strContent);
		$pdf = new HTML2FPDF();
		$pdf->AddPage();
		$pdf->writeHTML($strContent);
		$pdf->Output($doc_dst);
	}

	function pdf2swf($doc_src, $doc_dst, $swf_v = 9) {
		if (!is_file($doc_src)) {
			return false;
		}

		$cmd = CONVERT_PDF2SWF.' -T '.$swf_v.' '.$doc_src.' -o '.$doc_dst;
//		exec($cmd.' 2>&1', $this->_output);
		exec($cmd.' 2>&1');

		return $this->isSuccess();
	}

	function jpeg2swf($doc_src, $doc_dst, $swf_v = 9) {
		if (!is_file($doc_src)) {
			return false;
		}
		$cmd = CONVERT_JPEG2SWF.' -T '.$swf_v.' '.$doc_src.' -o '.$doc_dst;
		exec($cmd.' 2>&1', $this->_output);

		return $this->isSuccess();
	}
	
	function imagickRotate($doc_src, $doc_dst, $degress = 90) {
		if (!is_file($doc_src)) {
			return false;
		}
		
		$cmd = CONVERT_IMAGICK.' -rotate '.$degress.' '.$doc_src.' '.$doc_dst;
	
		exec($cmd.' 2>&1', $this->_output);

		if ($this->isSuccess()) {
			@unlink($doc_src);
			Ak_FileSystem_File::rename($doc_dst, $doc_src);
		}

		return $this->isSuccess();
	}

	function createPreview($fileBaseName, $page = "") {
		set_time_limit(0);

		$isAccepted = false;
		$accepted = explode(",","A,AI,ART,ARW,AVI,AVS,B,BGR,BIE,BMP,BMP2,BMP3,BRF,BRG,C,CALS,CAPTION,CIN,CIP,CLIP,CLIPBOARD,CMYK,CMYKA,CR2, CRW, CUR, CUT, DCM, DCR, DCX, DDS, DFONT, DJVU, DNG, DOT, DPS, DPX, EMF, EPDF, EPI, EPS, EPS2, EPS3, EPSF, EPSI, EPT, EPT2, EPT3, ERF, EXR, FAX, FITS, FPX, FRACTAL, FTS, G, G3, GBR, GIF, GIF87, GRADIENT, GRAY, GRB, HALD, HISTOGRAM, HRZ, HTM, HTML, ICB, ICO, ICON, INFO, INLINE, IPL, ISOBRL, JBG, JBIG, JNG, JP2, JPC, JPEG, JPG, JPX, K, K25, KDC, LABEL, M, M2V, M4V, MAP, MAT, MATTE, MIFF, MNG, MONO, MOV, MP4, MPC, MPEG, MPG, MRW, MSL, MSVG, MTV, MVG, NEF, NULL, O, ORF, OTB, OTF, PAL, PALM, PAM, PATTERN, PBM, PCD, PCDS, PCL, PCT, PCX, PDB, PDF, PDFA, PEF, PFA, PFB, PFM, PGM, PGX, PICON, PICT, PIX, PJPEG, PLASMA, PNG, PNG24, PNG32, PNG8, PNM, PPM, PREVIEW, PS, PS2, PS3, PSD, PTIF, PWP, R, RADIAL-GRADIENT, RAF, RAS, RBG, RGB, RGBA, RGBO, RLA, RLE, SCR, SCT, SFW, SGI, SHTML, SR2, SRF, STEGANO, SUN, SVG, SVGZ, TEXT, TGA, THUMBNAIL, TIFF, TIF, TIFF64, TILE, TIM, TTC, TTF, TXT, UBRL, UIL, UYVY, VDA, VICAR, VID, VIFF, VST, WBMP, WMF, WMFWIN32, WMV, WMZ, WPG, X, X3F, XBM, XC, XCF, XPM, XPS, XV, XWD, Y, YCbCr, YCbCrA, YUV, DOC, DOCX, XLS, XLSX, PPT, PPTX, CSV, EML,jnt,odg,odp,ods,odt");  //

		$officeFileExt = array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'jnt', 'odg', 'odp', 'ods', 'odt');   //'txt',

		$tmp = pathinfo($fileBaseName);
		$filename = $tmp['filename'];
		$extension = strtolower($tmp['extension']);

		$convertFrom = $this->_filePath.$fileBaseName;

		for ($i = 0; $i < count($accepted); $i++) {
			if (strtolower($extension) == strtolower(trim($accepted[$i]))){
				$isAccepted = true;
				break;
			}
		}

		if (!$isAccepted) {
			return false;
		}

		if (!is_file($convertFrom)) {
			return false;
		}

		if (in_array($extension, $officeFileExt)) {
			// convert to pdf first, and then convert pdf to swf;
			$tmp = AkString::getMicroString();

			if (IS_TEST) {		// convert on local, use PHP openoffice code:COM;
				$doc_src = 'file:///'.UPLOAD_PATH_STATIC.$fileBaseName;
				$doc_dst = 'file:///'.CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->office2pdf($doc_src, $doc_dst)) {
					return false;
				}
			} else { 		// convert on server, use JAVA code;
				$doc_src = UPLOAD_PATH_STATIC.$fileBaseName;
				$doc_dst = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->openOfficeDocumentConvert($doc_src, $doc_dst)) {
					return false;
				}
			}


			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_src);
		} elseif ($extension == 'txt') {
			$tmp = AkString::getMicroString();
			$doc_src = UPLOAD_PATH_STATIC.$fileBaseName;
			$doc_dst_txt = CONVERT_PATH_STATIC.$filename.$tmp.'.txt';

			$tmp_name = $doc_src;

			$encode = AkString::getFileEncode($doc_src);

			if ($encode == 'GB2312' || $encode == 'GBK' || $encode == 'ASCII') {
				$cmd = sprintf(CONVERT_GBK2UTF8, $encode, $doc_src, $doc_dst_txt);
				exec($cmd, $this->_output);

				if (empty($this->_output)) {		// convert to utf-8 success;
					$tmp_name = $doc_dst_txt;
				}
			}

			if (IS_TEST) {
				$doc_src = 'file:///'.$tmp_name;
				$doc_dst = 'file:///'.CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->office2pdf($doc_src, $doc_dst)) {
					return false;
				}
			} else {
				$doc_src = $tmp_name;
				$doc_dst = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->openOfficeDocumentConvert($doc_src, $doc_dst)) {
					return false;
				}
			}

			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_dst_txt);
			@unlink($doc_src);
		} elseif ($extension == 'eml') {
			/*require_once(LIB_PATH.'html2fpdf/html2fpdf.php');
			$tmpFile = UPLOAD_PATH_STATIC.$filename.'.html';
			$srcFile = UPLOAD_PATH_STATIC.$fileBaseName;

			Ak_FileSystem_File::copy($srcFile, $tmpFile);

			$tmp = AkString::getMicroString();
			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->html2pdf($tmpFile, $doc_src);

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_src);
			@unlink($tmpFile);*/

			$tmpFile = UPLOAD_PATH_STATIC.$filename.'.txt';
			$srcFile = UPLOAD_PATH_STATIC.$fileBaseName;

			$content_tmp = file_get_contents($srcFile);
			$content_tmp = quoted_printable_decode($content_tmp);

			$content_tmp = iconv('ISO-8859-1', 'utf-8', $content_tmp);
			$content_tmp = AkString::German_decode($content_tmp);

			file_put_contents($tmpFile, $content_tmp);

			//			Ak_FileSystem_File::copy($srcFile, $tmpFile);

			$tmp = AkString::getMicroString();
			$doc_src = $tmpFile;//UPLOAD_PATH_STATIC.$fileBaseName;
			$doc_dst_txt = CONVERT_PATH_STATIC.$filename.$tmp.'.txt';

			$tmp_name = $doc_src;

			$encode = AkString::getFileEncode($doc_src);

			if ($encode == 'GB2312' || $encode == 'GBK' || $encode == 'ASCII') {
				$cmd = sprintf(CONVERT_GBK2UTF8, $encode, $doc_src, $doc_dst_txt);
				exec($cmd, $this->_output);

				if (empty($this->_output)) {		// convert to utf-8 success;
					$tmp_name = $doc_dst_txt;
				}
			}

			if (IS_TEST) {
				$doc_src = 'file:///'.$tmp_name;
				$doc_dst = 'file:///'.CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->office2pdf($doc_src, $doc_dst)) {
					return false;
				}
			} else {
				$doc_src = $tmp_name;
				$doc_dst = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

				if (!$this->openOfficeDocumentConvert($doc_src, $doc_dst)) {
					return false;
				}
			}

			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_dst_txt);
			@unlink($doc_src);
			@unlink($tmpFile);
		} elseif ($extension == 'html') {
			$srcFile = UPLOAD_PATH_STATIC.$fileBaseName;
			$tmp = AkString::getMicroString();
			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->html2pdf($srcFile, $doc_src);

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_src);
		} elseif ($extension == 'pdf') {
			// convert to swf;
			$doc_src = UPLOAD_PATH_STATIC.$fileBaseName;
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->pdf2swf($doc_src, $doc_dst);
		} elseif ($extension == '__tif') {
			$tmp = AkString::getMicroString();
			$doc_src = UPLOAD_PATH_STATIC.$fileBaseName;
			$doc_dst_p = CONVERT_PATH_STATIC.$filename.'.jpg';

			$cmd = CONVERT_IMAGICK.CONVERT_IMAGICK_PAR.$doc_src." ".$doc_dst_p;

			exec($cmd.' 2>&1', $this->_output);

			if (!$this->isSuccess()) {
				return false;
			}
		} elseif ($extension == 'tif') {
			$tmp = AkString::getMicroString();
			$doc_src = UPLOAD_PATH_STATIC.$fileBaseName;
			$doc_dst_p = CONVERT_PATH_STATIC.$filename.$tmp.'.pdf';

			$cmd = CONVERT_IMAGICK." -size 600x721 -geometry 1728x2292 ".$doc_src." ".$doc_dst_p;

			exec($cmd.' 2>&1', $this->_output);

			if (!$this->isSuccess()) {
				return false;
			}

			$doc_src = $doc_dst_p;
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->pdf2swf($doc_src, $doc_dst);

			@unlink($doc_dst_p);
		} else {
			$tmp = AkString::getMicroString();
			$convertTo = CONVERT_PATH.$filename.$tmp.".jpg";

			$cmd = CONVERT_IMAGICK.CONVERT_IMAGICK_PAR.$convertFrom.$page." ".$convertTo;
			exec($cmd.' 2>&1', $this->_output);

			if (!$this->isSuccess()) {
				return false;
			}

			// TODO: convert to swf;
			$doc_src = CONVERT_PATH_STATIC.$filename.$tmp.'.jpg';
			$doc_dst = CONVERT_PATH_STATIC.$filename.'.swf';

			$this->jpeg2swf($doc_src, $doc_dst);

			@unlink($convertTo);
		}

		return $this->isSuccess();
	}

	/**
	 * check the exec output
	 * if error happen, return false, else return true.
	 *
	 * @return bool
	 */
	function isSuccess() {
		if (!empty($this->_output)) {
			foreach ($this->_output as $val) {
				if (stripos($val, 'error') !== false) {
					$hasError = true;
					break;
				}
			}

			if ($hasError) {
				return false;
			}
		}

		return true;
	}
}