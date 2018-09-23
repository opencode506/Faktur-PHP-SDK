<?php

namespace opencode506\Faktur;

use opencode506\Faktur\Interfaces\SicInterface;

/**
 * Auth representa los eventos comunes que se necesitan para
 * generar los comprobantes electrónicos
 * 
 * @author Open Code 506 community <opencode506@gmail.com>
 * @since 1.0.0
 */
class Sic implements SicInterface {

    /**
     * @var Indica en que ambiente se está ejecutando las acciones
     */
    private $environment;
    /**
     * @var URL en donde se realizan las consultas de los datos de los
     * contrinuyentes
     */
    private $sicHostWS;

    /**
     * Constructor
     */
    public function __construct() 
    {
        // Establecemos algunos valores necesarios para la consulta SIC Web
        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('soap.wsdl_cache_ttl', 900);
        ini_set('default_socket_timeout', 30);

        $this->sicHostWS = 'http://' . self::SIC_IP . '/' . self::SIC_WEB_SERVICE;
    }

    /**
     * findByDocumentId
     * 
     * Permite obtener la información de una contribuyente por medio
     * de su número de identificación
     *
     * @param [type] $documentId    Número de identificación
     * @param string $origin        Tipo de identificación, puede ser Fisico, Juridico o DIMEX
     * @return void
     */    
    public function findByDocumentId($documentId, $origin = 'Juridico')
    {
        try {  

            $return = [];

            // Opciones que se utilizan para consumir el web service
            $options = [
                'uri'                => 'http://schemas.xmlsoap.org/soap/envelope/',
                'style'              => SOAP_RPC,
                'use'                => SOAP_ENCODED,
                'soap_version'       => SOAP_1_1,
                'cache_wsdl'         => WSDL_CACHE_NONE,
                'connection_timeout' => 30,
                'trace'              => true,
                'encoding'           => 'UTF-8',
                'exceptions'         => true,
                'compression'        => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE
            ];
        

            /**
             * El webservice en Hacienda hace la consulta utilizando los valores 
             * de parámetro que no estén vacíos, así que se puede hacer una consulta
             *  haciendo combinaciones.
             */
            $params = [
                'origen'      => $origin, // Fisico,  Juridico o DIMEX
                'cedula'      => $documentId
            ];
            
            // URL para hacer request
            $wsdl = $this->sicHostWS;
        
            // Consumimos el wen service
            $soap = new \SoapClient($wsdl, $options);
            $response = $soap->ObtenerDatos($params);
            $soapResponse = $response->ObtenerDatosResult->any;

            // Transformamos el response enviado por el web service en hacienda
            $xml = str_replace(["diffgr:", "msdata:"], '', $soapResponse);
            $xml = "<package>" . $xml . "</package>";
            $data = simplexml_load_string($xml);

            
            if ($origin == 'Fisico') {
                // Response a la consulta de contribuyente fisico
                $return = [
                    'cedula' => isset($data->diffgram->DocumentElement->Table->CEDULA[0]) ? $data->diffgram->DocumentElement->Table->CEDULA[0] : '',
                    'apellido1' => isset($data->diffgram->DocumentElement->Table->APELLIDO1[0]) ? $data->diffgram->DocumentElement->Table->APELLIDO1[0] : '',
                    'apellido2' => isset($data->diffgram->DocumentElement->Table->APELLIDO2[0]) ? $data->diffgram->DocumentElement->Table->APELLIDO2[0] : '',
                    'nombre1' => isset($data->diffgram->DocumentElement->Table->NOMBRE1[0]) ? $data->diffgram->DocumentElement->Table->NOMBRE1[0] : '',
                    'nombre2' => isset($data->diffgram->DocumentElement->Table->NOMBRE2[0]) ? $data->diffgram->DocumentElement->Table->NOMBRE2[0] : '',
                    'adm' => isset($data->diffgram->DocumentElement->Table->ADM[0]) ? $data->diffgram->DocumentElement->Table->ADM[0] : '',
                    'ori'=> isset($data->diffgram->DocumentElement->Table->ORI[0]) ? $data->diffgram->DocumentElement->Table->ORI[0] : ''
                ];
            } elseif ($origin == 'Juridico') {
                // Response a la consulta de contribuyente juridico
                $return = [
                    'cedula' => isset($data->diffgram->DocumentElement->Table->CEDULA[0]) ? $data->diffgram->DocumentElement->Table->CEDULA[0] : '',
                    'nombre' => isset($data->diffgram->DocumentElement->Table->NOMBRE[0]) ? $data->diffgram->DocumentElement->Table->NOMBRE[0] : '',
                    'adm' => isset($data->diffgram->DocumentElement->Table->ADM[0]) ? $data->diffgram->DocumentElement->Table->ADM[0] : '',
                    'ori'=> isset($data->diffgram->DocumentElement->Table->ORI[0]) ? $data->diffgram->DocumentElement->Table->ORI[0] : ''
                ];
            } 
             
            return $return;

        } catch (\SoapFault $fault) {
            return [
                'code' => 500,
                'message' => $fault->getMessage()
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * findByName
     *
     * Permite obtener la información de una contribuyente por medio
     * de su nombre, apellido o razón social
     * 
     * @param array $params Indica los campos con los que se hará la 
     *                     consulta.  Para personas FISICA el array  debe ser construido
     *                     de la siguiente forma:
     *                     ```php
     *                           $consulta = [
     *                              'NOMBRE1' => 'Juan',
     *                              'NOMBRE2' => 'Arnoldo',
     *                              'APELLIDO1' => 'Perez',
     *                              'APELLIDO2' => 'Gallardo'
     *                           ]; 
     *                     ```
     *                     Para consultar personas JURIDICAS la consulta debe ser
     *                     construida de la siguiente forma:
     *                     ```php
     *                           $consulta = [
     *                              'RAZON' => 'Coporación ABC'
     *                           ]; 
     *                     ```
     *                 
     * @param string $origin  Indica si la consulta se hará a personas juridicas 
     *                        o personas físicas
     * @return array  Se retorna un array con todas las coincidencias enviadas por
     *                el web service de Hacienda
     */
    public function findByName($query, $origin = 'Juridico')
    {
        try {

            $return = [];

            // Opciones que se utilizan para consumir el web service
            $options = [
                'uri'                => 'http://schemas.xmlsoap.org/soap/envelope/',
                'style'              => SOAP_RPC,
                'use'                => SOAP_ENCODED,
                'soap_version'       => SOAP_1_1,
                'cache_wsdl'         => WSDL_CACHE_NONE,
                'connection_timeout' => 30,
                'trace'              => true,
                'encoding'           => 'UTF-8',
                'exceptions'         => true,
                'compression'        => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE
            ];

            /**
             * El webservice en Hacienda hace la consulta utilizando los valores 
             * de parámetro que no estén vacíos, así que se puede hacer una consulta
             *  haciendo combinaciones.
             */
            if ($origin == 'Juridico') {
                $params = [
                    'origen' => $origin, // Fisico,  Juridico o DIMEX
                    'razon'  => $query['RAZON']
                ];
            } elseif ($origin == 'Fisico') {
                $params = [
                    'origen' => $origin, // Fisico,  Juridico o DIMEX
                    'ape1'   => isset($query['APELLIDO1']) ? $query['APELLIDO1'] : '',
                    'ape2'   => isset($query['APELLIDO2']) ? $query['APELLIDO2'] : '',
                    'nomb1'  => isset($query['NOMBRE1']) ? $query['NOMBRE1'] : '',
                    'nomb2'  => isset($query['NOMBRE2']) ? $query['NOMBRE2'] : ''
                ];
            }

            $wsdl = $this->sicHostWS;
        
            $soap = new \SoapClient($wsdl, $options);
            $response = $soap->ObtenerDatos($params);
            $soapResponse = $response->ObtenerDatosResult->any;

            $xml = str_replace(["diffgr:", "msdata:"], '', $soapResponse);
            $xml = "<package>" . $xml . "</package>";
            $data = simplexml_load_string($xml);
            $results = $data->diffgram->DocumentElement->Table;

            foreach ($results as $result) {
                if ($origin == 'Juridico') {
                    // Response a la consulta de contribuyente juridico
                    $return[] = [
                        'cedula' => $result->CEDULA[0],
                        'razon' => $result->NOMBRE[0],
                        'adm' =>  $result->ADM[0],
                        'ori' => $result->ORI[0]
                    ];
                } elseif ($origin == 'Fisico') { 
                    // Response a la consulta de contribuyente fisico
                    $return[] = [
                        'cedula' => $result->CEDULA[0],
                        'nombre1' => $result->NOMBRE1[0],
                        'nombre2' => $result->NOMBRE2[0],
                        'apellido1' => $result->APELLIDO1[0],
                        'apellido2' => $result->APELLIDO2[0],
                        'adm' =>  $result->ADM[0],
                        'ori' => $result->ORI[0]
                    ];
                }
            }

            return $return;

        } catch (\SoapFault $fault) {
            return [
                'code' => 500,
                'message' => $fault->getMessage()
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}
