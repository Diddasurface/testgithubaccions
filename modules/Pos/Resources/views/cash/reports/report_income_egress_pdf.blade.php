<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte Caja</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-spacing: 0;
            border: 1px solid black;
        }

        .celda {
            text-align: center;
            padding: 5px;
            border: 0.1px solid black;
        }

        th {
            padding: 5px;
            text-align: center;
            border: 0.1px solid #0088cc;
        }

        .title {
            font-weight: bold;
            padding: 5px;
            font-size: 20px !important;
            text-decoration: underline;
        }

        p > strong {
            margin-left: 5px;
            font-size: 12px;
        }

        thead tr th {
            font-weight: bold;
            background: #0088cc;
            color: white;
            text-align: center;
        }

        .width-custom {
            width: 50%
        }
    </style>
</head>
<body>
<div>
    <p align="center" class="title">
        <strong>Reporte de ingresos y egresos</strong><br>
        <strong>Método de pago efectivo - Destino caja</strong>
    </p>
</div>
<div style="margin-top:10px; margin-bottom:20px;">
    <table>
        <tr>
            <td class="td-custom width-custom">
                <p>
                    <strong>Empresa:
                    </strong>{{$data['company_name']}}
                </p>
            </td>
            <td class="td-custom">
                <p>
                    <strong>Fecha reporte:
                    </strong>{{date('Y-m-d')}}
                </p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p>
                    <strong>Ruc:
                    </strong>
                    {{$data['company_number']}}
                </p>
            </td>
            <td class="width-custom">
                <p>
                    <strong>Establecimiento:
                    </strong>
                    {{$data['establishment_address']}} -
                    {{$data['establishment_department_description']}}
                    - {{$data['establishment_district_description']}}
                </p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p>
                    <strong>Vendedor:
                    </strong>
                    {{$data['cash_user_name']}}
                </p>
            </td>
            <td class="td-custom">
                <p>
                    <strong>Fecha y hora
                        apertura:
                    </strong>
                    {{$data['cash_date_opening']}} {{$data['cash_time_opening']}}
                </p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p>
                    <strong>
                        Estado de caja:
                    </strong>
                    {{($data['cash_state']) ? 'Aperturada':'Cerrada'}}
                </p>
            </td>
            @if(!$data['cash_state'])
                <td class="td-custom">
                    <p>
                        <strong>
                            Fecha y hora cierre:
                        </strong>
                        {{$data['cash_date_closed']}} {{$data['cash_time_closed']}}
                    </p>
                </td>
            @endif
        </tr>
        <br>
        <tr>
            <td colspan="2" class="td-custom">
                <p>
                    <strong>
                        Montos de operación:
                    </strong>
                </p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p>
                    <strong>
                        Total Ingresos:
                    </strong>
                    S/ {{$data['total_income']}}
                </p>
            </td>
            <td class="td-custom">
                <p>
                    <strong>
                        Total Egresos:
                    </strong>
                    S/ {{ $data['total_egress'] }}
                </p>
            </td>
        </tr>
        <tr>
            <td class="td-custom">
                <p>
                    <strong>
                        Saldo final:
                    </strong>
                    S/ {{$data['total_balance']}}
                </p>
            </td>
        </tr>

    </table>
</div>
@if(count($data_payments) > 0)
    <div class="">
        <div class=" ">
            <table>
                <thead>
                <tr>
                    <th>
                        #
                    </th>
                    <th>
                        Tipo transacción
                    </th>
                    <th>
                        Tipo documento
                    </th>
                    <th>
                        Documento
                    </th>
                    <th>
                        Fecha emisión
                    </th>
                    <th>
                        Cliente/Proveedor
                    </th>
                    <th>
                        N° Documento
                    </th>
                    <th>
                        Moneda
                    </th>
                    <th>
                        Total
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($data_payments as $key => $value)
                    <tr>
                        <td class="celda">
                            {{ $loop->iteration }}
                        </td>
                        <td class="celda">
                            {{ $value['type_transaction_description'] }}
                        </td>
                        <td class="celda">
                            {{ $value['document_type_description'] }}
                        </td>
                        <td class="celda">
                            {{ $value['number_full'] }}
                        </td>
                        <td class="celda">
                            {{ $value['date_of_issue'] }}
                        </td>
                        <td class="celda">
                            {{ $value['acquirer_name'] }}
                        </td>
                        <td class="celda">
                            {{ $value['acquirer_number'] }}
                        </td>
                        <td class="celda">
                            {{ $value['currency_type_id'] }}
                        </td>
                        <td class="celda">
                            {{ $value['payment'] }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="callout callout-info">
        <p>No se encontraron registros.
        </p>
    </div>
@endif
</body>
</html>
