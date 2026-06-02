<?php
// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------
// | @copyright (c) 原点 All rights reserved.
// +----------------------------------------------------------------------
// | Author: 原点 <467490186@qq.com>
// +----------------------------------------------------------------------
// | Date: 2025/9/18
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace yuandian\WebmanNacos\Provider\V1;

use Workerman\Http\Response;
use yuandian\WebmanNacos\AbstractProvider;

class AuthProvider extends AbstractProvider
{
    public function login(string $username, string $password): Response
    {
        return $this->request('POST', 'nacos/v1/auth/users/login', [
            'query'       => ['username' => $username],
            'form_params' => ['password' => $password],
        ]);
    }
}
