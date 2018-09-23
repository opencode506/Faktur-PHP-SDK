<?php

namespace opencode506\Faktur\Interfaces;

interface AuthInterface 
{

/**
     * Indica el ambiente de producción en hacienda para
     * obtener el token de autorización
     */
    const IDP_PRODUCTION = [
        'URL_TOKEN'  => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
        'CLIENT_ID'  => 'api-prod'
    ];
    /**
     * Indica el ambiente de prueba en hacienda para
     * obtener el token de autorización
     */
    const IDP_SANDBOX = [
        'URL_TOKEN'  => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
        'CLIENT_ID'  => 'api-stag'
    ];
    
}