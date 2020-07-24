<?php
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
        $this->namespace = '/my-namespace/v1';//przestrzen nazw
        $this->resource_name = 'posts';//nazwa zasobu
    }

    // Register our routes.
    public function register_routes()
    {   //rej pierwszej trasy
        // e.g - http://localhost/nonmulti/wp-json/my-namespace/v1/posts/.
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',//pobieranie
                'callback' => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),//walidacja uprawnien
            ),
            // Register our schema callback.
            'schema' => array($this, 'get_item_schema'),
        ));
        // rej drugiej trasy- uyzcie reg exp przy rej nazwy zasobu
        /*
         * umoliwi dodanie id posta na koniec trasy co spowoduje wyswietlenie tylko i wylacznie tego
         * konkretnego posta- wyr reg wymusz id jako liczbe int
         * e.g - http://localhost/nonmulti/wp-json/my-namespace/v1/posts/18
         */
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
    { // jesli user nie moze miec podgladu zasobu posta
        //uwaga nie dzila jak nalezy lub jest zle przeze mnie in terpretowane
        // po zalogowaniu sie nie dzila prawidlowo- w celu odblokowania skasuj operator negacji przed current user can.
        if (current_user_can('read')) {
            //authorization_status_code def linia 246
            return new WP_Error('rest_forbidden', esc_html__('You cannot view the post resource.'), array('status' => $this->authorization_status_code()));
        }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    //uwaga!! jest to callback dla pierwszej trtasy!!
    public function get_items($request)
    {
        $args = array(
            'post_per_page' => 5,
        );
        $posts = get_posts($args);

        $data = array();
        //jesli brak postow zwwroc odpowied przerwij program
        if (empty($posts)) {
            return rest_ensure_response($data);
        }
        //rozbicie tablicy postow na pojedyncze posty
        //i przygotowanie ich do posrttaci wp rest api
        foreach ($posts as $post) {
            $response = $this->prepare_item_for_response($post, $request);//def. 915
            $data[] = $this->prepare_response_for_collection($response);//def.941
        }

        // Return all of our comment response data.
        return rest_ensure_response($data);
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    //dla drugiej  zarejstroanej trasy
    // skasuj  operator negacji  sprzed current user can w razie nmeidizLANIA
    public function get_item_permissions_check($request)
    {
        if (current_user_can('read')) {
            return new WP_Error('rest_forbidden', esc_html__('You cannot view the post ressource.'), array('status' => $this->authorization_status_code()));
        }
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    //uwaga!! jest to callback dla drugiej trasy
    //jest to pobranie pojedynczego posta z a pomoca jego id
    // .
    public function get_item($request)//request jako wbud instanacja klasy wp rest resourrces
    {
        $id = (int)$request['id'];
        $post = get_post($id);

        if (empty($post)) {
            return rest_ensure_response(array());
        }

        $response = $this->prepare_item_for_response($post, $request);//def.917

        // Return all of our post response data.
        return $response;
    }

    /**
     * Matches the post data to the schema we want.
     * przyrownaj dane posta do schematu kotry cchesz osoagnaac
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     * przygotuj pozycje do odpowiedzi
     *  param post i request obydwa wbudowane
     */
    public function prepare_item_for_response($post, $request)
    {
        $post_data = array();
        //utworzenie schematu
        $schema = $this->get_item_schema($request);//def 971

        // We are also renaming the fields to more understandable names.
        if (isset($schema['properties']['id'])) {
            $post_data['id'] = (int)$post->ID;
        }
        // jesli w schmemacie istnieje wlasciwowsc content to zaladuj do tablicy postdata pod indexem
        //content wartosc wykonanego filtra the contntent
        if (isset($schema['properties']['content'])) {
            $post_data['content'] = apply_filters('the_content', $post->post_content, $post);
        }
        // zwroc tak przygotowane dane  do postaci wp rest api
        return rest_ensure_response($post_data);
    }

    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     *
     * Przygotowanie zbioru kolekcji odpowiedzi
     *
     *
     */
    public function prepare_response_for_collection($response)
    {
        // jesli obiekt response nie jest instanacja wp rest response to zworc odpowiedz i przerwi  program
        if (!($response instanceof WP_REST_Response)) {
            return $response;
        }
        //pobierz dane z odpowiedzi
        $data = (array)$response->get_data();

        //tworzenie obiektu klasy  wp rest server (wp build in  class)
        $server = rest_get_server();
        // jesli w instanacji klasy wp rest server  o nazwie server istnieje  metoda
        //get_compact_response_links to wywolaj ja z parametrem response i wartosc przypisz do $links
        if (method_exists($server, 'get_compact_response_links')) {
            //njprwd tworzenie linkow post
            $links = call_user_func(array($server, 'get_compact_response_links'), $response);
        } else {
            //gdy nie ma get_compact_response_links to uzyj get response links do tworzenia linkow
            $links = call_user_func(array($server, 'get_response_links'), $response);
        }
        //jesli istnieja linki do indexu _links tablicy dta przypisz te linki
        if (!empty($links)) {
            $data['_links'] = $links;
        }
        // zwroc kolekcje pzygotiowanycj linkow
        return $data;
    }

    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema($request)
    {    //njprwd sprwdzenie czy mamay do czynieni z wp 5.3 i wzwyz obslugujacym schematy i caly wp rest api
        //jesli wlsciwisosc schema nie isntniej przerwij apke
        if ($this->schema) {
            // Since WordPress 5.3, the schema can be cached in the $schema property.
            return $this->schema;
        }
        // stworzenie schemtu
        $this->schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4
            //uzywany tutaj schemat to draft 04.
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource
            //wlasciwowsc tytul  wskazuje na zasob - post z jkiam bedzie wspolpracowal schemat
            //schemt bedzie operowal na obiektach.
            'title' => 'post',
            'type' => 'object',
            // In JSON Schema you can specify object properties in the properties attribute
            //okreslamy w schemacie jason jakie beda wlasciwossci obiektu w atrybucie wlasciwosci(properties).
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
        // zwrcaamy schemt
        // uwaga zwewroc uwage ze schemat nie jest def w tej klasie
        //jest to prawidlowe uzycie - jego wartosc zostanie napisana przez wlasciwosc schemt jednej kklas
        //obslugujacej wp rrst api wbud w  wp
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
{   // utowrzenie obiektu klasy My_REST_Posts_Controller()
    //bezposrednie stworznei poprzez konstruktor klasy:
    // $this->namespace = '/my-namespace/v1';//przestrzen nazw
    // $this->resource_name = 'posts';//nazwa zasobu
    $controller = new My_REST_Posts_Controller();
    //wywolanie register_routes - metoda ta "spina wszytskie metody  zdefiniowane w klasie "
    //brak koniecznosci wywolywania poszczegolnych metod osobno wszytsko gotowe do uzycia .
    $controller->register_routes();


}


add_action('rest_api_init', 'prefix_register_my_rest_routes');

// uwaga sprubij wlaczyc plik z [poziomu klasy
function learningWordPress_resources() {

    wp_enqueue_style('style', get_stylesheet_uri());
    wp_enqueue_script('main_js', get_template_directory_uri() . '/js/main.js', NULL, 1.0, true);

    /* binding with WP REST API , magicData is representation key for data sending from php to js*/
    wp_localize_script('main_js', 'magicalData', array(
        'nonce' => wp_create_nonce('wp_rest'),//usage in JS :magicalData.nonce
        'siteURL' => get_site_url()  //usage in JS :magicalData.siteURL
    ));

}

add_action('wp_enqueue_scripts', 'learningWordPress_resources');

//
/*
 *
 * $controllerrr = new My_REST_Posts_Controller();
$test = $controllerrr->get_item_schema($request);
var_dump($test);
 */


