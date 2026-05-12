<?php

return [
    
    'secret' => getenv('JWT_SECRET') ?: 'lottery_jwt_secret_key_2024_change_in_production',
    
    
    'ttl' => (int)(getenv('JWT_TTL') ?: 7200),
    
    
    'refresh_ttl' => (int)(getenv('JWT_REFRESH_TTL') ?: 604800),
    
    
    'algo' => 'HS256',
    
    
    'issuer' => 'lottery-backend',
];

