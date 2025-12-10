<?php

it('returns a successful response for the open orders endpoint', function () {
    $response = $this->get('/api/orders');

    $response->assertStatus(200);
});
