<?php

return [
    // model config
    'customer_model' => 'App\Customer', 
    'user_model' => 'App\User',        

    // authentication guards
    'customer_guard' => 'customer',     
    'user_guard' => 'web',            

    
    'default_status_id' => 1,
    'default_priority_id' => 1,
    'default_category_id' => 1,
];