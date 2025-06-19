<!DOCTYPE html>
<html lang="es">
    <head>
        <style>

        </style>
    </head>
    <body>
        @if(!empty($record))
            <div class="" >
                <div class=" " >
                    <table class="table" width="100%">
                        @php
                            function withoutRounding($number, $total_decimals) {
                                $number = (string)$number;
                                if($number === '') {
                                    $number = '0';
                                }
                                if(strpos($number, '.') === false) {
                                    $number .= '.';
                                }
                                $number_arr = explode('.', $number);

                                $decimals = substr($number_arr[1], 0, $total_decimals);
                                if($decimals === false) {
                                    $decimals = '0';
                                }

                                $return = '';
                                if($total_decimals == 0) {
                                    $return = $number_arr[0];
                                } else {
                                    if(strlen($decimals) < $total_decimals) {
                                        $decimals = str_pad($decimals, $total_decimals, '0', STR_PAD_RIGHT);
                                    }
                                    $return = $number_arr[0] . '.' . $decimals;
                                }
                                return $return;
                            }
                        @endphp
                   
                        <tr>
                            @for($j=0; $j < $format; $j++)
                                @if($format == 1)
                                   <td class="celda" style="text-align: center; padding: 5px; font-size: 12px; vertical-align: top;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%;">
                                        
                                        {{-- Nombre --}}
                                        @if($has_Name == 'true')
                                            <div style="font-weight: bold; margin-bottom: 2px;">{{ $record->name }}</div>
                                        @endif

                                        <div style="display: flex; justify-content: center; gap: 10px;">
                                            @if($has_Model == 'true')
                                                <span>MOD: {{ $record->model }}</span>
                                            @endif
                                            @if($has_Codin == 'true')
                                                <span>COD: {{ $record->internal_id }}</span>
                                            @endif
                                        </div>

                                        {{-- Código de barras --}}
                                        <div style="margin-top: 5px;">
                                           @php
                                                $colour = [0,0,0];
                                                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                                echo '<img style="width:' . ($barcode_width * 65) . 'px; max-height: ' . $barcode_height . 'px;" 
                                                       src="data:image/png;base64,' . base64_encode(
                                                            $generator->getBarcode(
                                                            $record->barcode,
                                                            $generator::TYPE_CODE_128,
                                                            $barcode_width,
                                                            $barcode_height,
                                                            $colour
                                                            )
                                                        ) . '">';
                                                @endphp
                                            <div>{{ $record->barcode }}</div>
                                        </div>

                                    </div>
                                </td>
                                @elseif($format == 2)
                                    <td class="celda" width="50%" style="text-align: center; padding-bottom: 10px; font-size: 9px; vertical-align: top;">
                                        <table width="100%" class="table">
                                            @if($has_Name == 'true')
                                                <tr>
                                                    <td colspan="2" style="text-align: center;">
                                                        <b>{{ $record->name ? $record->name : $record->description }}</b>
                                                    </td>
                                                </tr>
                                            @endif

                                            @php
                                                $show_price = \App\Models\Tenant\Configuration::first()->isShowPriceBarcodeTicket();
                                            @endphp

                                            <tr>
                                                <td colspan="{{ $show_price ? 1 : 2 }}" style="text-align: center;">
                                                    @if($has_Model == 'true') MOD: {{ $record->model }} <br> @endif
                                                    @if($has_Codin == 'true') COD: {{ $record->internal_id }} @endif
                                                </td>
                                                @if($show_price)
                                                    <td style="text-align: center;">
                                                        <strong>{{ $record->currency_type->symbol }} {{ round($record->sale_unit_price, 2) }}</strong>
                                                    </td>
                                                @endif
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="text-align: center;">
                                                    <p>
                                                        @php
                                                            $colour = [0,0,0];
                                                            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                                            echo '<img style="width:' . ($barcode_width * 65) . 'px; max-height: ' . $barcode_height . 'px;" 
                                                                src="data:image/png;base64,' . base64_encode(
                                                                    $generator->getBarcode(
                                                                        $record->barcode,
                                                                        $generator::TYPE_CODE_128,
                                                                        $barcode_width,
                                                                        $barcode_height,
                                                                        $colour
                                                                    )
                                                                ) . '">';
                                                        @endphp
                                                    </p>
                                                    <p>{{ $record->barcode }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                @else
                                <td class="celda" width="33%" style="text-align: center; padding-top: 10px; padding-bottom: 10px; font-size: 9px; vertical-align: top;">
                                  @if($has_Codin == 'true')    <p>{{ $record->internal_id }}</p>@endif
                                    <p>
                                        @php
                                        $colour = [0,0,0];
                                        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                        echo '<img style="width:' . ($barcode_width * 65) . 'px; max-height: ' . $barcode_height . 'px;" 
                                                src="data:image/png;base64,' . base64_encode(
                                                    $generator->getBarcode(
                                                    $record->barcode,
                                                    $generator::TYPE_CODE_128,
                                                    $barcode_width,
                                                    $barcode_height,
                                                    $colour
                                                    )
                                                    ) . '">';
                                        @endphp
                                    </p>
                                    <p>{{$record->barcode}}</p>
                                </td>
                                @endif
                            @endfor
                        </tr>
            
                    </table>
                </div>
            </div>
        @else
            <div>
                <p>No se encontraron registros.</p>
            </div>
        @endif
    </body>
</html>
