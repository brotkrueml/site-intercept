<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/bamboo',
    'POST',
    [
        'payload' => '"attachments":[{"color":"good","text":"<https://bamboo.typo3.com/browse/CORE-GTC-30266|T3G \u203a Apparel \u203a #25> passed. 6 passed. Manual run by <https://bamboo.typo3.com/browse/user/susanne.moog|Susanne Moog>","fallback":"T3G \u203a Apparel \u203a #25 passed. 6 passed. Manual run by Susanne Moog"}],"username":"Bamboo"}',
    ]
);