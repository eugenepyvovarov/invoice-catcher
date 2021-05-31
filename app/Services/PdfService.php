<?php
namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfService
{
    public static function pdfToArray($filePath)
    {
        // Parse pdf file and build necessary objects.
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $result = [];
        foreach ($pdf->getPages() as $page) {
            $result[] = $page->getTextArray();
        }

        return $result;
    }

    /**
    [0] =>
    [1] => Biljett, giltig 2020-12-10
    [2] => Linköping C - Sthlm Central
    [3] => Mikael Eriksson
    [4] => Biljetten är personlig och gäller tillsammans med giltig ID-handling*. Photo ID required.
    [5] => Bokningsnr: DXQ9269I
    [6] => . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . .
    [7] => Biljettnr: DXQ9269I0001
    [8] => S7b&n,r]ccc5]#$5-0&5	n&'%'#ns
    [9] => 067 304 370 277 592 706 850
    [10] => Linköping C
    [11] => 17.59
    [12] => Sthlm Central
    [13] => 19.38
    [14] => Tåg
    [15] => 542
    [16] => Vagn
    [17] => 4
    [18] => Plats
    [19] => 25
    [20] => , Fönster, Salong
    [21] => Internet ombord via ombord.sj.se
    [22] => Kan ombokas om avbokad före avgångstid
    [23] => *Godkänd id-handling är samtliga pass, nationella id-kort från EU-land samt körkort och id-kort från Norden. Dessutom accepteras
    [24] => Migrationsverkets LMA-kort/"kvitto på asylansökan" vid visering.
    [25] => När du köper en resa av oss ingår du och S7b+n%,!!,,,n.,n$4p*w*w*-.,n.!$#n+,p%%$+*+'%p$$*w*!&*+n4r*a
    [26] => resevillkor hittar du på www.sj.se. Om du har några frågor gällande våra resevillkor, eller andra frågor om din resa, är du välkommen att
    [27] => kontakta kundservice på 0771-75 75 75.
    [28] => Biljetten är såld av: www.sj.se
     */
    public static function parseTicketData($filePath)
    {
        // Parse pdf file and build necessary objects.
        foreach (static::pdfToArray($filePath) as $data) {
            $arr = [
                'person' => $data[3],
                'valid_at' => self::parseValidAt($data[1]),
                'destinations' => [
                    'from' => $data[10],
                    'to' => $data[12],
                ],
                'time' => [
                    'departure_at' => $data[11],
                    'arrival_at' => $data[13],
                ],
                'ticket_number' => self::parseTicketNumber($data[7]),
                'train_number' => $data[15],
                'carrige' => $data[17],
                'qr_number' => $data[9]
            ];

            // add dates
            $timeDep = explode('.', $arr['time']['departure_at']);
            $timeArr = explode('.', $arr['time']['arrival_at']);

            $arr['departure_at'] = now()->parse($arr['valid_at'])->hour($timeDep[0])->minute($timeDep[1]);
            $arr['arrival_at'] = now()->parse($arr['valid_at'])->hour($timeArr[0])->minute($timeArr[1]);
            $result[] = $arr;
        }
        return $result;
    }

    private static function parseValidAt($str)
    {
        preg_match('/\d{4}-\d{2}-\d{2}/', $str, $result);
        return $result[0];
    }

    private static function parseTicketNumber($str)
    {
        return str_replace('Biljettnr: ', '', $str);
    }

    public function gmailToPdfFile($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($gmail->html_body);

        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        //$dompdf->stream();
        $dompdf->output();
        Storage::put('file.pdf', $dompdf->output());
    }

}