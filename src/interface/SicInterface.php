<?php

namespace opencode506\Faktur\Interfaces;

interface SicInterface
{
    /**
     * Indica la dirección IP en donde se realiza las consultas
     * SIC
     */
    const SIC_IP = '196.40.56.20';
    /**
     * Indica el web service que se consume para las 
     * consultas SIC
     */
    const SIC_WEB_SERVICE = 'wsInformativasSICWEB/Service1.asmx?WSDL';
}