<?php
/**
 * Created by JetBrains PhpStorm.
 * User: manghel
 * Date: 9/19/13
 * Time: 7:45 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Ititi\ParsingBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentController extends Controller{

    public function homeAction(Request $request) {
        $file = $request->files->get('file');
        $resultRows = array();

        if(!empty($file)) {
            $fileName = $file->getPathName();
            $excelObj = $this->get('xls.load_xls5')->load($fileName);
            $data = $excelObj->getSheetByName('ConsEvidBuget')->toArray();
            $finalRowFormat = array(
                'location' => '',
                'account' => '',
                'documentType' => '',
                'documentDate' => '',
                'documentNo' => '',
                'place' => '',
                'observation' => '',
                'value' => 0,
                'multiple_prices_items' => array()
            );
            $iterationRow = array();
            $iterationRows = array();

            foreach ($data as $rowNo=>$rowInfo) {
                if ($rowNo < 2) {
                    continue;
                }

                if (!empty($rowInfo[0])) {
                    $location = $rowInfo[0];
                }
                if (!empty($rowInfo[1])) {
                    $account = $rowInfo[1];
                }
                if (!empty($rowInfo[2])) {
                    if (!empty($iterationRow)) {
                        $iterationRows[] = $iterationRow;
                    }
                    $iterationRow = $finalRowFormat;
                    $iterationRow['location'] = $location;
                    $iterationRow['account'] = $account;

                    $iterationRow['documentType'] = $rowInfo[2];
                    $iterationRow['documentDate'] = $rowInfo[3];
                    $iterationRow['documentNo'] = $rowInfo[4];
                    $iterationRow['place'] = $rowInfo[5];
                    $iterationRow['observation'] = $rowInfo[9];
                    $iterationRow['value'] = $rowInfo[10];
                }
                if (!empty($rowInfo[12]) && trim($rowInfo[12]) != 'Text7') {
                    if ($iterationRow['account'] == '6022') {

                        if(!array_key_exists($rowInfo[12],$iterationRow['multiple_prices_items'])) {
                            $iterationRow['multiple_prices_items'][$rowInfo[12]] = array(
                                    'item'      => $rowInfo[12],
                                    'unit_price_prod_quantity'=> $rowInfo[13]*$rowInfo[14],
                                    'count'     => $rowInfo[14]
                            );
                        } else {
                            $iterationRow['multiple_prices_items'][$rowInfo[12]]['count'] += $rowInfo[14];
                            $iterationRow['multiple_prices_items'][$rowInfo[12]]['unit_price_prod_quantity'] += $rowInfo[13]*$rowInfo[14];
                        }
                    }
                }
            }

            foreach ($iterationRows as $iterationRow) {
                if ($iterationRow['account'] == 6022) {
                    if (!empty($iterationRow['multiple_prices_items'])) {
                        foreach ($iterationRow['multiple_prices_items'] as $itemName=>$itemInfo) {
                            if ($itemInfo['count'] == 0) {
                                $avgPrice = $itemInfo['unit_price_prod_quantity'];
                            } else {
                                $avgPrice = $itemInfo['unit_price_prod_quantity'] / $itemInfo['count'];
                            }

                            unset($iterationRow['multiple_prices_items']);
                            $iterationRow['average_value'] = $avgPrice;
                            $iterationRow['observation'] = $itemName;
                            $resultRows[] = $iterationRow;
                        }
                    }
                } else {
                    if (array_key_exists('multiple_prices_items',$iterationRow)) {
                        unset($iterationRow['multiple_prices_items']);
                    }
                    $resultRows[] = $iterationRow;
                }
            }
        }

        if (!empty($resultRows)) {
            $this->downloadXlsFile($resultRows);
        }

        return $this->render('ItitiParsingBundle:Content:home.html.twig');
    }

    public function downloadXlsFile($data) {
        $excelService = $this->get('xls.service_xls5');
        $activeSheet = $excelService->excelObj->setActiveSheetIndex(0);
        $header = array(
            'location' => 'Categorie',
            'account' => 'Cont',
            'documentType' => 'Tip document',
            'documentDate' => 'Data document',
            'documentNo' => 'Numar document',
            'place' => 'Locatie',
            'observation' => 'Observatii',
            'value' => 'Valoare',
            'average_value' => 'Valoare medie',
        );
        array_unshift($data,$header);
        $activeSheet->fromArray($data);
        $excelService->excelObj->getActiveSheet()->setTitle('Sheet 1');

        //create the response
        $response = $excelService->getResponse();
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=Accountancy_' . date('Y-m-d') . '.xls');

        // If you are using a https connection, you have to set those two headers and use sendHeaders() for compatibility with IE <9
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->sendHeaders();
        $response->sendContent();
//        return $response;
    }
}