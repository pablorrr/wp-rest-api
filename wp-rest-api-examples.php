<?php

/* *DO skometowania PRZYKLADOWYCH KODOW REFERENCYJNYCH ZRODLOWYCH!!!!!*/
/*register your custom route*/

/*source:https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#resource-schema
*/

/*example of usage: http://localhost/nonmulti/wp-json/my-namespace/v1/comments
powoduje zarejstrowanie nowej customowej trasy z
okrslonym customowym  wynikowym JSON PRINTUJACY Ostatnie piec komentarzy*/


// Register our routes.
/*
 * register_rest_route - rejestruje customowa ttrase wp rest api
 * https://developer.wordpress.org/reference/functions/register_rest_route/
 * param:$namespace
(string) (Required) The first URL segment after core prefix. Should be unique to your package/plugin.

$route
(string) (Required) The base URL for route you are adding.

$args
(array) (Optional) Either an array of options for the endpoint, or an array of arrays for multiple methods.

Default value: array()

$override
(bool) (Optional) If the route already exists, should we override it? True overrides, false merges (with newer overriding if duplicate keys exist).

Default value: false
 */


function prefix_register_my_comment_route()
{
    register_rest_route('my-namespace/v1', '/comments', array(
        // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
        array(
            'methods' => 'GET',//njprwd metoda przeslania danych -jawna
            'callback' => 'prefix_get_comment_sample', //def linia 55
        ),
        // Register our schema callback.
        'schema' => 'prefix_get_comment_schema',//wywolabnie linia 98.,uzycie 103, definicja linia 163
    ));
}

add_action('rest_api_init', 'prefix_register_my_comment_route');
//restapi init - odpala gdy przeylany jezst zadaniew API
//https://developer.wordpress.org/reference/hooks/rest_api_init/

/**
 * Grabs the five most recent comments and outputs them as a rest response.
 *
 * @param WP_REST_Request $request Current request.
 */
function prefix_get_comment_sample($request)
{
    $args = array(
        'number' => 5,
    );
    $comments = get_comments($args);

    $data = array();
    /*
     * rest_ensure_response
     * https://developer.wordpress.org/reference/functions/rest_ensure_response/
     * Zapewnia, że odpowiedź REST jest obiektem odpowiedzi (dla spójności).
     * Parameters #Parameters
    $response
    (WP_HTTP_Response|WP_Error|mixed) (Required) Response to check.

    Top ↑
    Return #Return
    (WP_REST_Response|mixed) If response generated an error,
    WP_Error, if response is already an instance,
     WP_HTTP_Response, otherwise returns a new WP_REST_Response instance.
     *
     */
    if (empty($comments)) {
        return rest_ensure_response($data);//zwewroc odpowiedz jako obiekt wp rest api
    }

    foreach ($comments as $comment) {
        $response = prefix_rest_prepare_comment($comment, $request);//definicja linia 97
        $data[] = prefix_prepare_for_collection($response);// def 130
    }

    // Return all of our comment response data.
    return rest_ensure_response($data);
}

/**
 * Matches the comment data to the schema we want.
 *
 * @param WP_Comment $comment The comment object whose response is being prepared.
 */
function prefix_rest_prepare_comment($comment, $request)
{
    $comment_data = array();

    $schema = prefix_get_comment_schema();//def 163

    // We are also renaming the fields to more understandable names.
    //create id
    if (isset($schema['properties']['id'])) {
        $comment_data['id'] = (int)$comment->comment_ID;
    }
//create author
    if (isset($schema['properties']['author'])) {
        $comment_data['author'] = (int)$comment->user_id;
    }
//create content
    if (isset($schema['properties']['content'])) {
        $comment_data['content'] = apply_filters('comment_text', $comment->comment_content, $comment);
    }

    return rest_ensure_response($comment_data);
}

/**
 * Prepare a response for inserting into a collection of responses.
 *
 * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
 *
 * @param WP_REST_Response $response Response object.
 * @return array Response data, ready for insertion into collection data.
 */
function prefix_prepare_for_collection($response)
{
    if (!($response instanceof WP_REST_Response)) {//jesli zmienna resposnse nie jest instanacja klasy wp rest response
        return $response;//to zwroc odpowiedz i przerwij dzilaanie
    }

    $data = (array)$response->get_data();
    /*
     *
     * https://developer.wordpress.org/reference/functions/rest_get_server/
     * Retrieves the current REST server instance.
     * Instantiates a new instance if none exists already.
     * */
    $server = rest_get_server();//zwrca insrtnancje rest serwera
//check if method get compact... exists in object server
    if (method_exists($server, 'get_compact_response_links')) {
        //call user func- PHP- invoke func in first parameterer, second is arg sent to func
        // in this example method used with indicates  which instance comes from
        $links = call_user_func(array($server, 'get_compact_response_links'), $response);
    } else {
        $links = call_user_func(array($server, 'get_response_links'), $response);
    }

    if (!empty($links)) {
        $data['_links'] = $links;
    }

    return $data;
}

/**
 * Get our sample schema for comments.
 */
function prefix_get_comment_schema()
{
    $schema = array(
        // This tells the spec of JSON Schema we are using which is draft 4.
        '$schema' => 'http://json-schema.org/draft-04/schema#',
        // The title property marks the identity of the resource.
        'title' => 'comment',
        'type' => 'object',
        // In JSON Schema you can specify object properties in the properties attribute.
        'properties' => array(
            'id' => array(
                'description' => esc_html__('Unique identifier for the object.', 'my-textdomain'),
                'type' => 'integer',
                'context' => array('view', 'edit', 'embed'),
                'readonly' => true,
            ),
            'author' => array(
                'description' => esc_html__('The id of the user object, if author was a user.', 'my-textdomain'),
                'type' => 'integer',
            ),
            'content' => array(
                'description' => esc_html__('The content for the object.', 'my-textdomain'),
                'type' => 'string',
            ),
        ),
    );

    return $schema;
}

//////////////////////////////////////
///
/*sprawdza czyprzy nowow zdefiiowanej tyrasie jest dodoany  customowy endpoiont ktory jest
jednoczesnie argumentem 'my-arg*' */
/*
 * source:
 * */

/*
 * example of usage: http://localhost/nonmulti/wp-json/my-namespace/v1/schema-arg
 *
 * */


// Register our routes.
function prefix_register_my_arg_route()
{
    register_rest_route('my-namespace/v1', '/schema-arg', array(
        // Here we register our endpoint.
        array(
            'methods' => 'GET',
            'callback' => 'prefix_get_item',
            'args' => prefix_get_endpoint_args(),
        ),
    ));
}

// Hook registration into 'rest_api_init' hook.
add_action('rest_api_init', 'prefix_register_my_arg_route');

/**
 * Returns the request argument `my-arg` as a rest response.
 *
 * @param WP_REST_Request $request Current request.
 */
function prefix_get_item($request)
{
    // If we didn't use required in the schema this would throw an error when my arg is not set.
    return rest_ensure_response($request['my-arg']);
}

/**
 * Get the argument schema for this example endpoint.
 */
function prefix_get_endpoint_args()
{
    $args = array();

    // Here we add our PHP representation of JSON Schema.
    $args['my-arg'] = array(
        'description' => esc_html__('This is the argument our endpoint returns.', 'my-textdomain'),
        'type' => 'string',
        'validate_callback' => 'prefix_validate_my_arg',
        'sanitize_callback' => 'prefix_sanitize_my_arg',
        'required' => true,
    );

    return $args;
}

/**
 * Our validation callback for `my-arg` parameter.
 *
 * @param mixed $value Value of the my-arg parameter.
 * @param WP_REST_Request $request Current request object.
 * @param string $param The name of the parameter in this case, 'my-arg'.
 */
function prefix_validate_my_arg($value, $request, $param)
{
    $attributes = $request->get_attributes();

    if (isset($attributes['args'][$param])) {
        $argument = $attributes['args'][$param];
        // Check to make sure our argument is a string.
        if ('string' === $argument['type'] && !is_string($value)) {
            return new WP_Error('rest_invalid_param', sprintf(esc_html__('%1$s is not of type %2$s', 'my-textdomain'), $param, 'string'), array('status' => 400));
        }
    } else {
        // This code won't execute because we have specified this argument as required.
        // If we reused this validation callback and did not have required args then this would fire.
        return new WP_Error('rest_invalid_param', sprintf(esc_html__('%s was not registered as a request argument.', 'my-textdomain'), $param), array('status' => 400));
    }

    // If we got this far then the data is valid.
    return true;
}

/**
 * Our santization callback for `my-arg` parameter.
 *
 * @param mixed $value Value of the my-arg parameter.
 * @param WP_REST_Request $request Current request object.
 * @param string $param The name of the parameter in this case, 'my-arg'.
 */
function prefix_sanitize_my_arg($value, $request, $param)
{
    $attributes = $request->get_attributes();

    if (isset($attributes['args'][$param])) {
        $argument = $attributes['args'][$param];
        // Check to make sure our argument is a string.
        if ('string' === $argument['type']) {
            return sanitize_text_field($value);
        }
    } else {
        // This code won't execute because we have specified this argument as required.
        // If we reused this validation callback and did not have required args then this would fire.
        return new WP_Error('rest_invalid_param', sprintf(esc_html__('%s was not registered as a request argument.', 'my-textdomain'), $param), array('status' => 400));
    }

    // If we got this far then something went wrong don't use user input.
    return new WP_Error('rest_api_sad', esc_html__('Something went terribly wrong.', 'my-textdomain'), array('status' => 500));


}


/*
 * trasa vs punkty koncowe
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#routes-vs-endpoints
 * */

/*Trasy vs punkty końcowe #Trasy a punkty końcowe
Punkty końcowe to funkcje dostępne za pośrednictwem interfejsu API. Mogą to być na przykład pobieranie indeksu API, aktualizowanie postu lub usuwanie komentarza. Punkty końcowe wykonują określoną funkcję, przyjmując pewną liczbę parametrów i zwracając dane do klienta.

Trasa to „nazwa” używana do uzyskiwania dostępu do punktów końcowych, używana w adresie URL. Trasa może mieć wiele punktów końcowych powiązanych z nią, a jej użycie zależy od czasownika HTTP.

Na przykład za pomocą adresu URL http://example.com/wp-json/wp/v2/posts/123:

„Trasa” to wp/v2/posts/123- Trasa nie obejmuje, wp-jsonponieważ wp-jsonjest to podstawowa ścieżka dla samego interfejsu API.
Ta trasa ma 3 punkty końcowe:
GETwyzwala get_itemmetodę, zwracając dane postu do klienta.
PUTuruchamia update_itemmetodę, pobierając dane do aktualizacji i zwracając zaktualizowane dane postu.
DELETEuruchamia delete_itemmetodę, zwracając teraz usunięte dane postu do klienta.
Alarm:W witrynach bez ładnych bezpośrednich l
     *
     *
     *
     * */


/*
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#creating-endpoints
 *
 * */

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
/*e.g usage :http://localhost/nonmulti/wp-json/hello-world/v1/phrase
 return result "Hello World, this is the WordPress REST API"
 * */


/*
 *
 * ierwszym przekazanym argumentem register_rest_route()jest przestrzeń nazw, która umożliwia nam grupowanie naszych tras. Drugi przekazany argument to ścieżka zasobu lub baza zasobu. W naszym przykładzie zasób, który odzyskujemy, to „Hello World, to jest wyrażenie REST API WordPress”.
 * Trzeci argument to tablica opcji. Określamy, jakich metod może używać punkt końcowy i jakie
 *  wywołanie zwrotne powinno nastąpić, gdy punkt końcowy jest dopasowany (można zrobić więcej rzeczy, ale są to podstawy).

Trzeci argument pozwala nam również zapewnić wywołanie zwrotne uprawnień, które może ograniczyć dostęp do punktu końcowego tylko niektórym użytkownikom. Trzeci argument oferuje również sposób rejestrowania argumentów dla punktu końcowego, dzięki czemu żądania mogą modyfikować odpowiedź naszego punktu końcowego. Omówimy te pojęcia w części dotyczącej punktów końcowych tego przewodnika.
 *
 * */
function prefix_get_endpoint_phrase()
{
    // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
    return rest_ensure_response('Hello World, this is the WordPress REST API');
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function prefix_register_example_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('hello-world/v1', '/phrase', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods' => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'prefix_get_endpoint_phrase',
    ));
}

add_action('rest_api_init', 'prefix_register_example_routes');

///////////////////////////////////////////////////////////////
///
///
/*
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#creating-endpoints
 * */

/**
 * This is our callback function to return our products.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 *
 * usage:http://localhost/nonmulti/wp-json/my-shop/v1/products
 * returned result :{"1":"I am product 1","2":"I am product 2","3":"I am product 3"}
 */
function prefix_get_products($request)
{
    // In practice this function would fetch the desired data. Here we are just making stuff up.
    $products = array(
        '1' => 'I am product 1',
        '2' => 'I am product 2',
        '3' => 'I am product 3',
    );

    return rest_ensure_response($products);
}

/**
 * This is our callback function to return a single product.
 *
 * @param WP_REST_Request $request This function accepts a rest request to process data.
 */
function prefix_create_product($request)
{
    // In practice this function would create a product. Here we are just making stuff up.
    return rest_ensure_response('Product has been created');
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function prefix_register_product_routes()
{
    // Here we are registering our route for a collection of products and creation of products.
    register_rest_route('my-shop/v1', '/products', array(
        array(
            // By using this constant we ensure that when the WP_REST_Server changes, our readable endpoints will work as intended.
            'methods' => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => 'prefix_get_products',
        ),
        array(
            // By using this constant we ensure that when the WP_REST_Server changes, our create endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => 'prefix_create_product',
        ),
    ));
}

add_action('rest_api_init', 'prefix_register_product_routes');

/*
 * Metody HTTP #Metody HTTP
Metody HTTP są czasami nazywane czasownikami HTTP. Są to po prostu różne sposoby komunikowania się przez HTTP. Głównymi używanymi przez interfejs API REST WordPress są:

GET powinien być używany do pobierania danych z API.
POST należy wykorzystywać do tworzenia nowych zasobów (tj. użytkowników, postów, taksonomii).
PUT powinien być używany do aktualizacji zasobów.
DELETE powinien być używany do usuwania zasobów.
OPTIONS powinny być wykorzystane do zapewnienia kontekstu na temat naszych zasobów.
Należy zauważyć, że metody te nie są obsługiwane przez każdego klienta, ponieważ zostały wprowadzone w HTTP 1.1. Na szczęście interfejs API zapewnia obejście tych niefortunnych przypadków. Jeśli chcesz usunąć zasób, ale nie możesz wysłać DELETEżądania, możesz użyć _methodparametru lub X-HTTP-Method-Overridenagłówka w żądaniu. Jak to działa, wyślesz POSTzapytanie do https://ourawesomesite.com/wp-json/my-shop/v1/products/1?_method=DELETE. Teraz usuniesz produkt o numerze 1, nawet jeśli Twój klient nie mógł wysłać prawidłowej metody HTTP w żądaniu, lub być może istniała zapora ogniowa, która blokuje żądania DELETE.

Metoda HTTP, w połączeniu z trasą i wywołaniami zwrotnymi, stanowią rdzeń punktu końcowego
 *
 * */

/*
 * permission callback
 *  przydatne gdy  masz wrazliwe dane nie mogabyc publiczne tworzysz  callabacki pozwolen narejsestreowane jako endpoitns
 *
 */
/* https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#permissions-callback

/**
* This is our callback function that embeds our resource in a WP_REST_Response
*/

/*
 * usage : http://localhost/nonmulti/wp-json/my-plugin/v1/private-data
 *
 */
function prefix_get_private_data()
{
    // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
    return rest_ensure_response('This is private data.');
}

/**
 * This is our callback function that embeds our resource in a WP_REST_Response
 */
function prefix_get_private_data_permissions_check()
{
    // Restrict endpoint to only users who have the edit_posts capability.
    if (!current_user_can('edit_posts')) {
        return new WP_Error('rest_forbidden', esc_html__('OMG you can not view private data.', 'my-text-domain'), array('status' => 401));
    }

    // This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
    return true;
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function pprefix_register_example_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('my-plugin/v1', '/private-data', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods' => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'prefix_get_private_data',
        // Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
        'permission_callback' => 'prefix_get_private_data_permissions_check',
    ));
}

add_action('rest_api_init', 'pprefix_register_example_routes');


/*
 * przesylanie customowych argumentow
 *
 */
/*https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#arguments*/

//usage: http://localhost/nonmulti/wp-json/my-colors/v1/colors
//result :["blue","blue","red","red","green","green"]
/**
 * This is our callback function that embeds our resource in a WP_REST_Response
 */
function prefix_get_colors($request)
{
    // In practice this function would fetch the desired data. Here we are just making stuff up.
    $colors = array(
        'blue',
        'blue',
        'red',
        'red',
        'green',
        'green',
    );

    if (isset($request['filter'])) {
        $filtered_colors = array();
        foreach ($colors as $color) {
            if ($request['filter'] === $color) {
                $filtered_colors[] = $color;
            }
        }
        return rest_ensure_response($filtered_colors);
    }
    return rest_ensure_response($colors);
}

/**
 * We can use this function to contain our arguments for the example product endpoint.
 */
function prefix_get_color_arguments()
{
    $args = array();
    // Here we are registering the schema for the filter argument.
    $args['filter'] = array(
        // description should be a human readable description of the argument.
        'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
        // type specifies the type of data that the argument should be.
        'type' => 'string',
        // enum specified what values filter can take on.
        'enum' => array('red', 'green', 'blue'),
    );
    return $args;
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function ppprefix_register_example_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('my-colors/v1', '/colors', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods' => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'prefix_get_colors',
        // Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
        'args' => prefix_get_color_arguments(),
    ));
}

add_action('rest_api_init', 'ppprefix_register_example_routes');


/* https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#validation

/*
 * walidacja rest api
 *
 *uzycie wpisac adre z wpjason i zarejestrowana trasa oraz nalezy uzyc niewlsciwego argumentu w pasku adresu - o uzyciu argumentow mozna przeczytac w codex rest api
 */
/**
 * This is our callback function that embeds our resource in a WP_REST_Response
 */
function pprefix_get_colors($request)
{
    // In practice this function would fetch more practical data. Here we are just making stuff up.
    $colors = array(
        'blue',
        'blue',
        'red',
        'red',
        'green',
        'green',
    );

    if (isset($request['filter'])) {
        $filtered_colors = array();
        foreach ($colors as $color) {
            if ($request['filter'] === $color) {
                $filtered_colors[] = $color;
            }
        }
        return rest_ensure_response($filtered_colors);
    }
    return rest_ensure_response($colors);
}

/**
 * Validate a request argument based on details registered to the route.
 *
 * @param mixed $value Value of the 'filter' argument.
 * @param WP_REST_Request $request The current request object.
 * @param string $param Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function prefix_filter_arg_validate_callback($value, $request, $param)
{
    // If the 'filter' argument is not a string return an error.
    if (!is_string($value)) {
        return new WP_Error('rest_invalid_param', esc_html__('The filter argument must be a string.', 'my-text-domain'), array('status' => 400));
    }

    // Get the registered attributes for this endpoint request.
    $attributes = $request->get_attributes();

    // Grab the filter param schema.
    $args = $attributes['args'][$param];

    // If the filter param is not a value in our enum then we should return an error as well.
    if (!in_array($value, $args['enum'], true)) {
        return new WP_Error('rest_invalid_param', sprintf(__('%s is not one of %s'), $param, implode(', ', $args['enum'])), array('status' => 400));
    }
}

/**
 * We can use this function to contain our arguments for the example product endpoint.
 */
function pprefix_get_color_arguments()
{
    $args = array();
    // Here we are registering the schema for the filter argument.
    $args['filter'] = array(
        // description should be a human readable description of the argument.
        'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
        // type specifies the type of data that the argument should be.
        'type' => 'string',
        // enum specified what values filter can take on.
        'enum' => array('red', 'green', 'blue'),
        // Here we register the validation callback for the filter argument.
        'validate_callback' => 'prefix_filter_arg_validate_callback',
    );
    return $args;
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function prefixx_register_example_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('my-colors/v1', '/colors', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods' => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'pprefix_get_colors',
        // Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
        'args' => pprefix_get_color_arguments(),
    ));
}

add_action('rest_api_init', 'prefixx_register_example_routes');


/* https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#sanitizing*/

//sanityzacja

/**
 * This is our callback function that embeds our resource in a WP_REST_Response.
 *
 * The parameter is already sanitized by this point so we can use it without any worries.
 */
function prefixxx_get_item($request)
{
    if (isset($request['data'])) {
        return rest_ensure_response($request['data']);
    }

    return new WP_Error('rest_invalid', esc_html__('The data parameter is required.', 'my-text-domain'), array('status' => 400));
}

/**
 * Validate a request argument based on details registered to the route.
 *
 * @param mixed $value Value of the 'filter' argument.
 * @param WP_REST_Request $request The current request object.
 * @param string $param Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function prefix_data_arg_validate_callback($value, $request, $param)
{
    // If the 'data' argument is not a string return an error.
    if (!is_string($value)) {
        return new WP_Error('rest_invalid_param', esc_html__('The filter argument must be a string.', 'my-text-domain'), array('status' => 400));
    }
}

/**
 * Sanitize a request argument based on details registered to the route.
 *
 * @param mixed $value Value of the 'filter' argument.
 * @param WP_REST_Request $request The current request object.
 * @param string $param Key of the parameter. In this case it is 'filter'.
 * @return WP_Error|boolean
 */
function prefix_data_arg_sanitize_callback($value, $request, $param)
{
    // It is as simple as returning the sanitized value.
    return sanitize_text_field($value);
}

/**
 * We can use this function to contain our arguments for the example product endpoint.
 */
function prefix_get_data_arguments()
{
    $args = array();
    // Here we are registering the schema for the filter argument.
    $args['data'] = array(
        // description should be a human readable description of the argument.
        'description' => esc_html__('The data parameter is used to be sanitized and returned in the response.', 'my-text-domain'),
        // type specifies the type of data that the argument should be.
        'type' => 'string',
        // Set the argument to be required for the endpoint.
        'required' => true,
        // We are registering a basic validation callback for the data argument.
        'validate_callback' => 'prefix_data_arg_validate_callback',
        // Here we register the validation callback for the filter argument.
        'sanitize_callback' => 'prefix_data_arg_sanitize_callback',
    );
    return $args;
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function prefixxxx_register_example_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('my-plugin/v1', '/sanitized-data', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods' => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'prefixxx_get_item',
        // Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
        'args' => prefix_get_data_arguments(),
    ));
}

add_action('rest_api_init', 'prefixxxx_register_example_routes');

///////////////////////////////////////////////////
///
///
///
/// OOP
///
///
///
///
/// /////////////////////////////////////////////////////

/* https://developer.wordpress.org/rest-api/extending-the-rest-api/controller-classes/

/*
 * wp rerst api w ujeciu cusyomowej obiketowki- sa to cutom'owe kontrolery  wp rest api
 *  zawiera permissions callback
 * rejestracja tras
 *
 */


class My_REST_Posts_Controller
{

    // Here initialize our namespace and resource name.
    public function __construct()
    {
        $this->namespace = '/my-namespace/v1';
        $this->resource_name = 'posts';
    }

    // Register our routes.
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            // Register our schema callback.
            'schema' => array($this, 'get_item_schema'),
        ));
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_item'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
            ),
            // Register our schema callback.
            'schema' => array($this, 'get_item_schema'),
        ));
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check($request)
    {
        if (!current_user_can('read')) {
            return new WP_Error('rest_forbidden', esc_html__('You cannot view the post resource.'), array('status' => $this->authorization_status_code()));
        }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items($request)
    {
        $args = array(
            'post_per_page' => 5,
        );
        $posts = get_posts($args);

        $data = array();

        if (empty($posts)) {
            return rest_ensure_response($data);
        }

        foreach ($posts as $post) {
            $response = $this->prepare_item_for_response($post, $request);
            $data[] = $this->prepare_response_for_collection($response);
        }

        // Return all of our comment response data.
        return rest_ensure_response($data);
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_permissions_check($request)
    {
        if (!current_user_can('read')) {
            return new WP_Error('rest_forbidden', esc_html__('You cannot view the post resource.'), array('status' => $this->authorization_status_code()));
        }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item($request)
    {
        $id = (int)$request['id'];
        $post = get_post($id);

        if (empty($post)) {
            return rest_ensure_response(array());
        }

        $response = $this->prepare_item_for_response($post, $request);

        // Return all of our post response data.
        return $response;
    }

    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response($post, $request)
    {
        $post_data = array();

        $schema = $this->get_item_schema($request);

        // We are also renaming the fields to more understandable names.
        if (isset($schema['properties']['id'])) {
            $post_data['id'] = (int)$post->ID;
        }

        if (isset($schema['properties']['content'])) {
            $post_data['content'] = apply_filters('the_content', $post->post_content, $post);
        }

        return rest_ensure_response($post_data);
    }

    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection($response)
    {
        if (!($response instanceof WP_REST_Response)) {
            return $response;
        }

        $data = (array)$response->get_data();
        $server = rest_get_server();

        if (method_exists($server, 'get_compact_response_links')) {
            $links = call_user_func(array($server, 'get_compact_response_links'), $response);
        } else {
            $links = call_user_func(array($server, 'get_response_links'), $response);
        }

        if (!empty($links)) {
            $data['_links'] = $links;
        }

        return $data;
    }

    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema($request)
    {
        if ($this->schema) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }

        $this->schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title' => 'post',
            'type' => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties' => array(
                'id' => array(
                    'description' => esc_html__('Unique identifier for the object.', 'my-textdomain'),
                    'type' => 'integer',
                    'context' => array('view', 'edit', 'embed'),
                    'readonly' => true,
                ),
                'content' => array(
                    'description' => esc_html__('The content for the object.', 'my-textdomain'),
                    'type' => 'string',
                ),
            ),
        );

        return $this->schema;
    }

    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code()
    {

        $status = 401;

        if (is_user_logged_in()) {
            $status = 403;
        }

        return $status;
    }
}

// Function to register our new routes from the controller.
function prefix_register_my_rest_routes()
{
    $controller = new My_REST_Posts_Controller();
    $controller->register_routes();
}

add_action('rest_api_init', 'prefix_register_my_rest_routes');

////////////////////////////////////////////////////////////////////////////////////////////////////
///
///
///
///
///
///
/// ///////////////////////////////////////////////////////////////////////////


/* https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#using-register_rest_field*/

//rejestracja customowych pol wordprerss api
// uwaga!!! przykladów jest znacznie wiecej na tej stronie!!
// zadanie dowiedziec sie czym sa pola

add_action('rest_api_init', function () {
    register_rest_field('comment', 'karma', array(
        'get_callback' => function ($comment_arr) {
            $comment_obj = get_comment($comment_arr['id']);
            return (int)$comment_obj->comment_karma;
        },
        'update_callback' => function ($karma, $comment_obj) {
            $ret = wp_update_comment(array(
                'comment_ID' => $comment_obj->comment_ID,
                'comment_karma' => $karma
            ));
            if (false === $ret) {
                return new WP_Error(
                    'rest_comment_karma_failed',
                    __('Failed to update comment karma.'),
                    array('status' => 500)
                );
            }
            return true;
        },
        'schema' => array(
            'description' => __('Comment karma.'),
            'type' => 'integer'
        ),
    ));
});


/* https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/


/*dodawanie customowych punktów końcowych*/

/**
 * Grab latest post title by an author!
 *
 * @param array $data Options for the function.
 * @return string|null Post title for the latest,  * or null if none.
 */
function my_awesome_func($data)
{
    $posts = get_posts(array(
        'author' => $data['id'],
    ));

    if (empty($posts)) {
        return null;
    }

    return $posts[0]->post_title;
}

add_action('rest_api_init', function () {
    register_rest_route('myplugin/v1', '/author/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_awesome_func',
    ));
});


/* https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/#registering-a-custom-taxonomy-with-rest-api-support
*
*/
/*
 * integracja  customowych taxonomi z wp rest api
 *
 *
 */


/**
 * Register a genre post type, with REST API support
 *
 * Based on example at: https://codex.wordpress.org/Function_Reference/register_taxonomy
 */


//njprwd umozliwia podglad z apomoaca odp[owiedniego argumentu jako endpoints w trasie do podlgadu tej taxonomi
add_action('init', 'my_book_taxonomy', 30);
function my_book_taxonomy()
{

    $labels = array(
        'name' => _x('Genres', 'taxonomy general name'),
        'singular_name' => _x('Genre', 'taxonomy singular name'),
        'search_items' => __('Search Genres'),
        'all_items' => __('All Genres'),
        'parent_item' => __('Parent Genre'),
        'parent_item_colon' => __('Parent Genre:'),
        'edit_item' => __('Edit Genre'),
        'update_item' => __('Update Genre'),
        'add_new_item' => __('Add New Genre'),
        'new_item_name' => __('New Genre Name'),
        'menu_name' => __('Genre'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'genre'),
        'show_in_rest' => true,
        'rest_base' => 'genre',
        'rest_controller_class' => 'WP_REST_Terms_Controller',
    );

    register_taxonomy('genre', array('book'), $args);

}


/**
 * Add REST API support to an already registered post type.
 */

//jesli istnieje cpt book to dodoaj do specyfikacji rest api
add_filter('register_post_type_args', 'my_post_type_args', 10, 2);

function my_post_type_args($args, $post_type)
{

    if ('book' === $post_type) {
        $args['show_in_rest'] = true;

        // Optionally customize the rest_base or rest_controller_class
        $args['rest_base'] = 'books';
        $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
    }

    return $args;
}
