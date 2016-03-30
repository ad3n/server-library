<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Unit;

use OAuth2\Test\Base;

/**
 * @group Metadata
 */
class MetadataTest extends Base
{
    public function testMetadataProperties()
    {
        $metadata = $this->getMetadata();

        $this->assertTrue($metadata->has('scopes_supported'));
        $this->assertEquals(['scope1', 'scope2', 'scope3', 'scope4', 'openid'], $metadata->get('scopes_supported'));
        $this->assertEquals('{"issuer":"https:\/\/server.example.com","authorization_endpoint":"https:\/\/my.server.com\/authorize","token_endpoint":"https:\/\/my.server.com\/token","userinfo_endpoint":"https:\/\/my.server.com\/user_info","jwks_uri":"https:\/\/my.server.com\/jwks","registration_endpoint":"https:\/\/my.server.com\/register","scopes_supported":["scope1","scope2","scope3","scope4","openid"],"response_types_supported":["code","token","none","id_token","code id_token","id_token token","code id_token token"],"response_modes_supported":["query","fragment","form_post"],"grant_types_supported":["authorization_code","client_credentials","refresh_token","password","urn:ietf:params:oauth:grant-type:jwt-bearer"],"acr_values_supported":[],"subject_types_supported":["public"],"id_token_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"id_token_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"id_token_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"userinfo_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"userinfo_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"userinfo_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"request_object_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"request_object_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"request_object_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"token_endpoint_auth_methods_supported":["resource_server_custom_auth","private_key_jwt","none","client_secret_basic","client_secret_jwt","client_secret_post"],"token_endpoint_auth_signing_alg_values_supported":["HS256","HS512"],"token_endpoint_auth_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"token_endpoint_auth_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"display_values_supported":["page"],"claim_types_supported":false,"claims_supported":false,"service_documentation":"https:\/\/my.server.com\/documentation","claims_locales_supported":[],"ui_locales_supported":["en_US","fr_FR"],"claims_parameter_supported":false,"request_parameter_supported":true,"request_uri_parameter_supported":true,"require_request_uri_registration":true,"op_policy_uri":"https:\/\/my.server.com\/policy.html","op_tos_uri":"https:\/\/my.server.com\/tos.html"}', json_encode($metadata));

        $metadata->remove('authorization_endpoint');
        $this->assertEquals('{"issuer":"https:\/\/server.example.com","token_endpoint":"https:\/\/my.server.com\/token","userinfo_endpoint":"https:\/\/my.server.com\/user_info","jwks_uri":"https:\/\/my.server.com\/jwks","registration_endpoint":"https:\/\/my.server.com\/register","scopes_supported":["scope1","scope2","scope3","scope4","openid"],"response_types_supported":["code","token","none","id_token","code id_token","id_token token","code id_token token"],"response_modes_supported":["query","fragment","form_post"],"grant_types_supported":["authorization_code","client_credentials","refresh_token","password","urn:ietf:params:oauth:grant-type:jwt-bearer"],"acr_values_supported":[],"subject_types_supported":["public"],"id_token_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"id_token_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"id_token_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"userinfo_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"userinfo_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"userinfo_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"request_object_signing_alg_values_supported":["HS256","HS512","RS256","RS512"],"request_object_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"request_object_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"token_endpoint_auth_methods_supported":["resource_server_custom_auth","private_key_jwt","none","client_secret_basic","client_secret_jwt","client_secret_post"],"token_endpoint_auth_signing_alg_values_supported":["HS256","HS512"],"token_endpoint_auth_encryption_alg_values_supported":["A128KW","A256KW","A128GCMKW","A256GCMKW","PBES2-HS256+A128KW","PBES2-HS512+A256KW","RSA1_5","RSA-OAEP","RSA-OAEP-256"],"token_endpoint_auth_encryption_enc_values_supported":["A128GCM","A256GCM","A128CBC-HS256","A256CBC-HS512"],"display_values_supported":["page"],"claim_types_supported":false,"claims_supported":false,"service_documentation":"https:\/\/my.server.com\/documentation","claims_locales_supported":[],"ui_locales_supported":["en_US","fr_FR"],"claims_parameter_supported":false,"request_parameter_supported":true,"request_uri_parameter_supported":true,"require_request_uri_registration":true,"op_policy_uri":"https:\/\/my.server.com\/policy.html","op_tos_uri":"https:\/\/my.server.com\/tos.html"}', json_encode($metadata));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Configuration value with key "foo" does not exist.
     */
    public function testMetadataPropertyDoesNotExist()
    {
        $metadata = $this->getMetadata();

        $metadata->get('foo');
    }
}