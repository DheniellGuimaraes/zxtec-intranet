<?php
/*
Plugin Name: ZX Tec - Intranet de Serviços e Colaboradores
Description: Intranet para gestão de serviços, clientes, colaboradores e relatórios financeiros.
Version: 1.17.0
Author: ZX Tec
License: GPL2
Text Domain: zxtec-intranet
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

class ZXTEC_Intranet {
    const VERSION = '1.17.0';

    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_menu', array($this, 'register_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('zxtec_dashboard', array($this, 'dashboard_shortcode'));
        add_action('admin_post_zxtec_export_csv', array($this, 'export_csv'));
        add_action('admin_post_zxtec_export_excel', array($this, 'export_excel'));
        add_action('admin_post_zxtec_export_pdf', array($this, 'export_pdf'));
        add_action('admin_post_zxtec_export_historico_csv', array($this, 'export_historico_csv'));
        add_action('admin_post_zxtec_export_despesas_csv', array($this, 'export_despesas_csv'));
        add_action('admin_post_zxtec_export_mydespesas_csv', array($this, 'export_mydespesas_csv'));
        add_action('admin_post_zxtec_confirm_ag', array($this, 'confirm_agendamento'));
        add_action('admin_post_zxtec_recusar_ag', array($this, 'recusar_agendamento'));
        add_action('admin_post_zxtec_concluir_ag', array($this, 'concluir_agendamento'));
        add_action('admin_post_zxtec_add_despesa', array($this, 'add_despesa'));
        add_action('show_user_profile', array($this, 'user_profile_fields'));
        add_action('edit_user_profile', array($this, 'user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_user_profile'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile'));
        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
        register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate'));
    }

    public static function activate() {
        add_role('zxtec_colaborador', 'Colaborador ZX Tec', array(
            'read' => true,
            'edit_zxtec_agendamento' => true
        ));
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_assets() {
        wp_enqueue_style('tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
        wp_enqueue_style('zxtec-style', plugin_dir_url(__FILE__) . 'assets/style.css', array('tailwind'), self::VERSION);
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array('jquery'), null, true);
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('zxtec-map', plugin_dir_url(__FILE__) . 'assets/map.js', array('jquery', 'leaflet'), self::VERSION, true);
    }

    public function register_post_types() {
        register_post_type('zxtec_cliente', array(
            'labels' => array(
                'name' => 'Clientes',
                'singular_name' => 'Cliente'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title'),
            'menu_position' => 25,
        ));

        register_post_type('zxtec_servico', array(
            'labels' => array(
                'name' => 'Serviços',
                'singular_name' => 'Serviço'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title'),
            'menu_position' => 26,
        ));

        register_post_type('zxtec_agendamento', array(
            'labels' => array(
                'name' => 'Agendamentos',
                'singular_name' => 'Agendamento'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title'),
            'menu_position' => 27,
        ));

        register_post_type('zxtec_contrato', array(
            'labels' => array(
                'name' => 'Contratos',
                'singular_name' => 'Contrato'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title'),
            'menu_position' => 28,
        ));

        register_post_type('zxtec_despesa', array(
            'labels' => array(
                'name' => 'Despesas',
                'singular_name' => 'Despesa'
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title'),
            'menu_position' => 29,
        ));
    }

    public function register_meta_boxes() {
        add_meta_box('zxtec_cliente_info', 'Dados do Cliente', array($this, 'cliente_meta_box'), 'zxtec_cliente', 'normal');
        add_meta_box('zxtec_cliente_geo', 'Geolocalização', array($this, 'cliente_geo_box'), 'zxtec_cliente', 'side');
        add_meta_box('zxtec_servico_info', 'Dados do Serviço', array($this, 'servico_meta_box'), 'zxtec_servico', 'normal');
        add_meta_box('zxtec_agendamento_info', 'Dados do Agendamento', array($this, 'agendamento_meta_box'), 'zxtec_agendamento', 'normal');
        add_meta_box('zxtec_agendamento_fin', 'Dados Financeiros', array($this, 'agendamento_fin_box'), 'zxtec_agendamento', 'side');
        add_meta_box('zxtec_contrato_info', 'Dados do Contrato', array($this, 'contrato_meta_box'), 'zxtec_contrato', 'normal');
        add_meta_box('zxtec_despesa_info', 'Dados da Despesa', array($this, 'despesa_meta_box'), 'zxtec_despesa', 'normal');
    }

    public function cliente_meta_box($post) {
        $cpf = get_post_meta($post->ID, '_zxtec_cpf', true);
        $endereco = get_post_meta($post->ID, '_zxtec_endereco', true);
        $contato = get_post_meta($post->ID, '_zxtec_contato', true);
        $email = get_post_meta($post->ID, '_zxtec_email', true);
        echo '<p><label>CPF/CNPJ:<br><input type="text" name="zxtec_cpf" value="' . esc_attr($cpf) . '"/></label></p>';
        echo '<p><label>Endereço:<br><input type="text" name="zxtec_endereco" value="' . esc_attr($endereco) . '"/></label></p>';
        echo '<p><label>Contato:<br><input type="text" name="zxtec_contato" value="' . esc_attr($contato) . '"/></label></p>';
        echo '<p><label>E-mail:<br><input type="email" name="zxtec_email" value="' . esc_attr($email) . '"/></label></p>';
    }

    public function cliente_geo_box($post) {
        $lat = get_post_meta($post->ID, '_zxtec_lat', true);
        $lng = get_post_meta($post->ID, '_zxtec_lng', true);
        echo '<p><label>Latitude:<br><input type="text" name="zxtec_lat" value="' . esc_attr($lat) . '"/></label></p>';
        echo '<p><label>Longitude:<br><input type="text" name="zxtec_lng" value="' . esc_attr($lng) . '"/></label></p>';
    }

    public function servico_meta_box($post) {
        $preco = get_post_meta($post->ID, '_zxtec_preco', true);
        $categoria = get_post_meta($post->ID, '_zxtec_categoria', true);
        echo '<p><label>Preço do Serviço (R$):<br><input type="number" step="0.01" name="zxtec_preco" value="' . esc_attr($preco) . '"/></label></p>';
        echo '<p><label>Categoria:<br><input type="text" name="zxtec_categoria" value="' . esc_attr($categoria) . '"/></label></p>';
    }

    public function agendamento_meta_box($post) {
        $cliente = get_post_meta($post->ID, '_zxtec_ag_cliente', true);
        $data = get_post_meta($post->ID, '_zxtec_ag_data', true);
        $tecnico = get_post_meta($post->ID, '_zxtec_ag_tecnico', true);
        $servico = get_post_meta($post->ID, '_zxtec_ag_servico', true);
        wp_dropdown_pages(array(
            'post_type' => 'zxtec_cliente',
            'name' => 'zxtec_ag_cliente',
            'selected' => $cliente,
            'show_option_none' => 'Selecione o cliente'
        ));
        $servicos = get_posts(array('post_type' => 'zxtec_servico', 'numberposts' => -1));
        echo '<p><label>Serviço:<br><select name="zxtec_ag_servico"><option value="">Selecione o serviço</option>';
        foreach($servicos as $s){
            $selected = $servico == $s->ID ? 'selected' : '';
            echo '<option value="' . $s->ID . '" ' . $selected . '>' . esc_html($s->post_title) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>Data:<br><input type="datetime-local" name="zxtec_ag_data" value="' . esc_attr($data) . '"/></label></p>';
        wp_dropdown_users(array(
            'name' => 'zxtec_ag_tecnico',
            'role' => 'zxtec_colaborador',
            'selected' => $tecnico,
            'show_option_none' => 'Selecione o colaborador'
        ));
    }

    public function agendamento_fin_box($post) {
        $comissao = get_post_meta($post->ID, '_zxtec_comissao', true);
        $status = get_post_meta($post->ID, '_zxtec_status', true);
        $just = get_post_meta($post->ID, '_zxtec_justificativa', true);
        echo '<p>Comissão calculada: R$ ' . number_format(floatval($comissao), 2, ',', '.') . '</p>';
        echo '<p>Status atual: ' . esc_html($status) . '</p>';
        if ($just) echo '<p>Justificativa: ' . esc_html($just) . '</p>';
    }

    public function contrato_meta_box($post) {
        $cliente = get_post_meta($post->ID, '_zxtec_ct_cliente', true);
        $inicio = get_post_meta($post->ID, '_zxtec_ct_inicio', true);
        $fim = get_post_meta($post->ID, '_zxtec_ct_fim', true);
        $plano = get_post_meta($post->ID, '_zxtec_ct_plano', true);
        $status = get_post_meta($post->ID, '_zxtec_ct_status', true);
        wp_dropdown_pages(array(
            'post_type' => 'zxtec_cliente',
            'name' => 'zxtec_ct_cliente',
            'selected' => $cliente,
            'show_option_none' => 'Selecione o cliente'
        ));
        echo '<p><label>Início:<br><input type="date" name="zxtec_ct_inicio" value="' . esc_attr($inicio) . '"/></label></p>';
        echo '<p><label>Fim:<br><input type="date" name="zxtec_ct_fim" value="' . esc_attr($fim) . '"/></label></p>';
        echo '<p><label>Plano:<br><input type="text" name="zxtec_ct_plano" value="' . esc_attr($plano) . '"/></label></p>';
        echo '<p><label>Status:<br><select name="zxtec_ct_status">';
        $opts = array('ativo' => 'Ativo', 'encerrado' => 'Encerrado');
        foreach ($opts as $val => $label) {
            $sel = $status === $val ? 'selected' : '';
            echo '<option value="' . esc_attr($val) . '" ' . $sel . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label></p>';
    }

    public function despesa_meta_box($post) {
        $valor = get_post_meta($post->ID, '_zxtec_dp_valor', true);
        $data = get_post_meta($post->ID, '_zxtec_dp_data', true);
        $desc = get_post_meta($post->ID, '_zxtec_dp_desc', true);
        echo '<p><label>Valor (R$):<br><input type="number" step="0.01" name="zxtec_dp_valor" value="' . esc_attr($valor) . '"/></label></p>';
        echo '<p><label>Data:<br><input type="date" name="zxtec_dp_data" value="' . esc_attr($data) . '"/></label></p>';
        echo '<p><label>Descrição:<br><textarea name="zxtec_dp_desc" rows="3">' . esc_textarea($desc) . '</textarea></label></p>';
    }

    public function save_meta_boxes($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (isset($_POST['zxtec_cpf']))
            update_post_meta($post_id, '_zxtec_cpf', sanitize_text_field($_POST['zxtec_cpf']));
        if (isset($_POST['zxtec_endereco']))
            update_post_meta($post_id, '_zxtec_endereco', sanitize_text_field($_POST['zxtec_endereco']));
        if (isset($_POST['zxtec_contato']))
            update_post_meta($post_id, '_zxtec_contato', sanitize_text_field($_POST['zxtec_contato']));
        if (isset($_POST['zxtec_email']))
            update_post_meta($post_id, '_zxtec_email', sanitize_email($_POST['zxtec_email']));
        if (isset($_POST['zxtec_lat']))
            update_post_meta($post_id, '_zxtec_lat', sanitize_text_field($_POST['zxtec_lat']));
        if (isset($_POST['zxtec_lng']))
            update_post_meta($post_id, '_zxtec_lng', sanitize_text_field($_POST['zxtec_lng']));
        if (isset($_POST['zxtec_preco']))
            update_post_meta($post_id, '_zxtec_preco', floatval($_POST['zxtec_preco']));
        if (isset($_POST['zxtec_categoria']))
            update_post_meta($post_id, '_zxtec_categoria', sanitize_text_field($_POST['zxtec_categoria']));
        if (isset($_POST['zxtec_ag_cliente']))
            update_post_meta($post_id, '_zxtec_ag_cliente', intval($_POST['zxtec_ag_cliente']));
        if (isset($_POST['zxtec_ag_data']) && $_POST['zxtec_ag_data']) {
            update_post_meta($post_id, '_zxtec_ag_data', sanitize_text_field($_POST['zxtec_ag_data']));
        } elseif (isset($_POST['zxtec_ag_tecnico']) && $_POST['zxtec_ag_tecnico']) {
            $auto_time = $this->auto_schedule_time(intval($_POST['zxtec_ag_tecnico']));
            update_post_meta($post_id, '_zxtec_ag_data', $auto_time);
        }
        if (isset($_POST['zxtec_ag_tecnico'])) {
            update_post_meta($post_id, '_zxtec_ag_tecnico', intval($_POST['zxtec_ag_tecnico']));
        } elseif (isset($_POST['zxtec_ag_servico']) && isset($_POST['zxtec_ag_cliente'])) {
            $auto = $this->auto_assign_tecnico(intval($_POST['zxtec_ag_servico']), intval($_POST['zxtec_ag_cliente']));
            update_post_meta($post_id, '_zxtec_ag_tecnico', $auto);
        }
        if (isset($_POST['zxtec_ag_servico']))
            update_post_meta($post_id, '_zxtec_ag_servico', intval($_POST['zxtec_ag_servico']));
        if ('zxtec_agendamento' === get_post_type($post_id) && isset($_POST['zxtec_ag_servico'])) {
            $preco = get_post_meta(intval($_POST['zxtec_ag_servico']), '_zxtec_preco', true);
            $comissao = floatval($preco) * 0.1;
            update_post_meta($post_id, '_zxtec_comissao', $comissao);
            $tecnico = isset($_POST['zxtec_ag_tecnico']) ? intval($_POST['zxtec_ag_tecnico']) : get_post_meta($post_id, '_zxtec_ag_tecnico', true);
            if ($tecnico) {
                $user = get_userdata($tecnico);
                $cliente = intval($_POST['zxtec_ag_cliente']);
                $cliente_nome = get_the_title($cliente);
                $servico_nome = get_the_title(intval($_POST['zxtec_ag_servico']));
                $data = sanitize_text_field($_POST['zxtec_ag_data']);
                $msg = "Novo agendamento:\nCliente: $cliente_nome\nServi\xC3\xA7o: $servico_nome\nData: $data";
                wp_mail($user->user_email, 'Novo Agendamento', $msg);
            }
        }

        if (isset($_POST['zxtec_ct_cliente']))
            update_post_meta($post_id, '_zxtec_ct_cliente', intval($_POST['zxtec_ct_cliente']));
        if (isset($_POST['zxtec_ct_inicio']))
            update_post_meta($post_id, '_zxtec_ct_inicio', sanitize_text_field($_POST['zxtec_ct_inicio']));
        if (isset($_POST['zxtec_ct_fim']))
            update_post_meta($post_id, '_zxtec_ct_fim', sanitize_text_field($_POST['zxtec_ct_fim']));
        if (isset($_POST['zxtec_ct_plano']))
            update_post_meta($post_id, '_zxtec_ct_plano', sanitize_text_field($_POST['zxtec_ct_plano']));
        if (isset($_POST['zxtec_ct_status']))
            update_post_meta($post_id, '_zxtec_ct_status', sanitize_text_field($_POST['zxtec_ct_status']));

        if (isset($_POST['zxtec_dp_valor']))
            update_post_meta($post_id, '_zxtec_dp_valor', floatval($_POST['zxtec_dp_valor']));
        if (isset($_POST['zxtec_dp_data']))
            update_post_meta($post_id, '_zxtec_dp_data', sanitize_text_field($_POST['zxtec_dp_data']));
        if (isset($_POST['zxtec_dp_desc']))
            update_post_meta($post_id, '_zxtec_dp_desc', sanitize_textarea_field($_POST['zxtec_dp_desc']));
    }

    public function register_admin_pages() {
        add_menu_page('ZX Tec Admin', 'ZX Tec Admin', 'manage_options', 'zxtec_admin_panel', array($this, 'render_admin_panel'), 'dashicons-admin-generic');
        add_menu_page('ZX Tec Colaborador', 'ZX Tec Colaborador', 'read', 'zxtec_colaborador_dashboard', array($this, 'render_colaborador_dashboard'), 'dashicons-groups');
        add_submenu_page('zxtec_colaborador_dashboard', 'Financeiro', 'Financeiro', 'read', 'zxtec_colab_financeiro', array($this, 'render_colab_financeiro_page'));
        add_submenu_page('zxtec_colaborador_dashboard', 'Despesas', 'Despesas', 'read', 'zxtec_colab_despesas', array($this, 'render_colab_despesas_page'));
        add_submenu_page('zxtec_colaborador_dashboard', 'Histórico', 'Histórico', 'read', 'zxtec_colab_historico', array($this, 'render_colab_historico_page'));
        add_submenu_page('zxtec_admin_panel', 'Relatórios Financeiros', 'Relatórios', 'manage_options', 'zxtec_financeiro', array($this, 'render_financeiro_page'));
        add_submenu_page('zxtec_admin_panel', 'Despesas Colaboradores', 'Despesas', 'manage_options', 'zxtec_despesas', array($this, 'render_despesas_page'));
        add_submenu_page('zxtec_admin_panel', 'Contratos Ativos', 'Contratos', 'manage_options', 'zxtec_contratos', array($this, 'render_contratos_page'));
        add_submenu_page('zxtec_admin_panel', 'Histórico de Instalações', 'Histórico', 'manage_options', 'zxtec_historico', array($this, 'render_historico_page'));
    }

    public function render_admin_panel() {
        $clientes = get_posts(array('post_type' => 'zxtec_cliente', 'numberposts' => -1));
        $cli_data = array();
        foreach ($clientes as $c) {
            $lat = get_post_meta($c->ID, '_zxtec_lat', true);
            $lng = get_post_meta($c->ID, '_zxtec_lng', true);
            if ($lat && $lng) {
                $cli_data[] = array('title' => $c->post_title, 'lat' => $lat, 'lng' => $lng);
            }
        }
        $tech_data = array();
        $users = get_users(array('role' => 'zxtec_colaborador'));
        foreach ($users as $u) {
            $lat = get_user_meta($u->ID, 'zxtec_lat', true);
            $lng = get_user_meta($u->ID, 'zxtec_lng', true);
            if ($lat && $lng) {
                $tech_data[] = array('name' => $u->display_name, 'lat' => $lat, 'lng' => $lng);
            }
        }
        echo '<div class="wrap"><h1>ZX Tec - Painel Administrativo</h1><div id="zxtec-map" class="zxtec-map"></div>';
        echo '<script>var ZXTEC_CLIENTS = ' . wp_json_encode($cli_data) . ';var ZXTEC_TECHS = ' . wp_json_encode($tech_data) . ';</script></div>';
    }

    public function render_financeiro_page() {
        echo '<div class="wrap"><h1>Relatórios Financeiros</h1>';
        $colaboradores = get_users(array('role' => 'zxtec_colaborador'));
        if ($colaboradores) {
            echo '<table class="widefat"><thead><tr><th>Colaborador</th><th>Total (R$)</th></tr></thead><tbody>';
            foreach ($colaboradores as $colab) {
                $args = array(
                    'post_type' => 'zxtec_agendamento',
                    'meta_key' => '_zxtec_ag_tecnico',
                    'meta_value' => $colab->ID,
                    'numberposts' => -1
                );
                $posts = get_posts($args);
                $total = 0;
                foreach ($posts as $p) {
                    $total += floatval(get_post_meta($p->ID, '_zxtec_comissao', true));
                }
                echo '<tr><td>' . esc_html($colab->display_name) . '</td><td>' . number_format($total, 2, ',', '.') . '</td></tr>';
            }
            echo '</tbody></table>';
            $export_csv = admin_url('admin-post.php?action=zxtec_export_csv');
            $export_excel = admin_url('admin-post.php?action=zxtec_export_excel');
            $export_pdf = admin_url('admin-post.php?action=zxtec_export_pdf');
            echo '<p>';
            echo '<a class="button" href="' . esc_url($export_csv) . '">Exportar CSV</a> ';
            echo '<a class="button" href="' . esc_url($export_excel) . '">Exportar Excel</a> ';
            echo '<a class="button" href="' . esc_url($export_pdf) . '">Exportar PDF</a>';
            echo '</p>';
        } else {
            echo '<p>Nenhum colaborador encontrado.</p>';
        }
        echo '</div>';
    }

    public function render_despesas_page() {
        echo '<div class="wrap"><h1>Despesas de Colaboradores</h1>';
        $despesas = get_posts(array('post_type' => 'zxtec_despesa', 'numberposts' => -1));
        if ($despesas) {
            echo '<table class="widefat"><thead><tr><th>Colaborador</th><th>Data</th><th>Descrição</th><th>Valor (R$)</th></tr></thead><tbody>';
            $total = 0;
            foreach ($despesas as $d) {
                $autor = get_userdata($d->post_author);
                $valor = get_post_meta($d->ID, '_zxtec_dp_valor', true);
                $data = get_post_meta($d->ID, '_zxtec_dp_data', true);
                $desc = get_post_meta($d->ID, '_zxtec_dp_desc', true);
                $total += floatval($valor);
                echo '<tr><td>' . esc_html($autor->display_name) . '</td><td>' . esc_html($data) . '</td><td>' . esc_html($desc) . '</td><td>' . number_format($valor,2,',','.') . '</td></tr>';
            }
            echo '<tr><th colspan="3" style="text-align:right">Total</th><th>' . number_format($total,2,',','.') . '</th></tr>';
            echo '</tbody></table>';
            $export = admin_url('admin-post.php?action=zxtec_export_despesas_csv');
            echo '<p><a class="button" href="' . esc_url($export) . '">Exportar CSV</a></p>';
        } else {
            echo '<p>Nenhuma despesa registrada.</p>';
        }
        echo '</div>';
    }

    public function render_contratos_page() {
        echo '<div class="wrap"><h1>Contratos Ativos</h1>';
        $args = array(
            'post_type' => 'zxtec_contrato',
            'meta_key' => '_zxtec_ct_status',
            'meta_value' => 'ativo',
            'numberposts' => -1
        );
        $contratos = get_posts($args);
        if ($contratos) {
            echo '<table class="widefat"><thead><tr><th>Cliente</th><th>Plano</th><th>Início</th><th>Fim</th></tr></thead><tbody>';
            foreach ($contratos as $c) {
                $cliente = get_post_meta($c->ID, '_zxtec_ct_cliente', true);
                $plano = get_post_meta($c->ID, '_zxtec_ct_plano', true);
                $inicio = get_post_meta($c->ID, '_zxtec_ct_inicio', true);
                $fim = get_post_meta($c->ID, '_zxtec_ct_fim', true);
                echo '<tr><td>' . esc_html(get_the_title($cliente)) . '</td><td>' . esc_html($plano) . '</td><td>' . esc_html($inicio) . '</td><td>' . esc_html($fim) . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Nenhum contrato ativo.</p>';
        }
        echo '</div>';
    }

    public function render_historico_page() {
        echo '<div class="wrap"><h1>Histórico de Instalações</h1>';
        $inicio = isset($_GET['inicio']) ? sanitize_text_field($_GET['inicio']) : '';
        $fim    = isset($_GET['fim']) ? sanitize_text_field($_GET['fim']) : '';
        $tec    = isset($_GET['tecnico']) ? intval($_GET['tecnico']) : 0;
        echo '<form method="get"><input type="hidden" name="page" value="zxtec_historico" />';
        echo '<p><label>Início:<br><input type="date" name="inicio" value="' . esc_attr($inicio) . '" /></label></p>';
        echo '<p><label>Fim:<br><input type="date" name="fim" value="' . esc_attr($fim) . '" /></label></p>';
        echo '<p><label>Técnico:<br><select name="tecnico"><option value="">Todos</option>';
        $users = get_users(array('role' => 'zxtec_colaborador'));
        foreach ($users as $u) {
            $sel = $tec == $u->ID ? ' selected' : '';
            echo '<option value="' . esc_attr($u->ID) . '"' . $sel . '>' . esc_html($u->display_name) . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><input type="submit" class="button" value="Filtrar"></p></form>';
        $meta = array(
            array('key' => '_zxtec_status', 'value' => 'concluido')
        );
        if ($inicio) $meta[] = array('key' => '_zxtec_ag_data', 'value' => $inicio, 'compare' => '>=');
        if ($fim)    $meta[] = array('key' => '_zxtec_ag_data', 'value' => $fim, 'compare' => '<=');
        if ($tec)    $meta[] = array('key' => '_zxtec_ag_tecnico', 'value' => $tec);
        $args = array(
            'post_type' => 'zxtec_agendamento',
            'meta_query' => $meta,
            'numberposts' => -1
        );
        $agendamentos = get_posts($args);
        $url = add_query_arg(array('action' => 'zxtec_export_historico_csv', 'inicio' => $inicio, 'fim' => $fim, 'tecnico' => $tec), admin_url('admin-post.php'));
        echo '<p><a class="button" href="' . esc_url($url) . '">Exportar CSV</a></p>';
        if ($agendamentos) {
            echo '<table class="widefat"><thead><tr><th>Cliente</th><th>Serviço</th><th>Técnico</th><th>Data</th></tr></thead><tbody>';
            foreach ($agendamentos as $a) {
                $cliente = get_post_meta($a->ID, '_zxtec_ag_cliente', true);
                $servico = get_post_meta($a->ID, '_zxtec_ag_servico', true);
                $tecnico = get_post_meta($a->ID, '_zxtec_ag_tecnico', true);
                $data = get_post_meta($a->ID, '_zxtec_ag_data', true);
                echo '<tr><td>' . esc_html(get_the_title($cliente)) . '</td><td>' . esc_html(get_the_title($servico)) . '</td><td>' . esc_html(get_userdata($tecnico)->display_name) . '</td><td>' . esc_html($data) . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Nenhum serviço concluído.</p>';
        }
        echo '</div>';
    }

    public function dashboard_shortcode() {
        ob_start();
        $this->render_colaborador_dashboard();
        return ob_get_clean();
    }

    public function render_colaborador_dashboard() {
        if (!current_user_can('read')) {
            wp_die('Acesso negado');
        }
        $args = array(
            'post_type' => 'zxtec_agendamento',
            'meta_key' => '_zxtec_ag_tecnico',
            'meta_value' => get_current_user_id()
        );
        $query = new WP_Query($args);
        echo '<div class="wrap"><h1>ZX Tec - Dashboard do Colaborador</h1>';
        if ($query->have_posts()) {
            echo '<table class="widefat"><thead><tr><th>Cliente</th><th>Serviço</th><th>Data</th><th>Rota</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
            while ($query->have_posts()) {
                $query->the_post();
                $cliente = get_post_meta(get_the_ID(), '_zxtec_ag_cliente', true);
                $servico = get_post_meta(get_the_ID(), '_zxtec_ag_servico', true);
                $data = get_post_meta(get_the_ID(), '_zxtec_ag_data', true);
                $status = get_post_meta(get_the_ID(), '_zxtec_status', true);
                $lat = get_post_meta($cliente, '_zxtec_lat', true);
                $lng = get_post_meta($cliente, '_zxtec_lng', true);
                $rota = '';
                if ($lat && $lng) {
                    $rota = '<a href="https://www.google.com/maps/dir/?api=1&destination=' . esc_attr($lat) . ',' . esc_attr($lng) . '" target="_blank">Mapa</a>';
                }
                $servico_nome = $servico ? get_the_title($servico) : '';
                $form_confirm = '';
                $form_recusa = '';
                $form_concluir = '';
                if ($status === 'confirmado') {
                    $form_concluir = '<form method="post" action="' . admin_url('admin-post.php') . '"><input type="hidden" name="action" value="zxtec_concluir_ag"><input type="hidden" name="ag_id" value="' . get_the_ID() . '"><input type="submit" class="button" value="Concluir"></form>';
                } elseif ($status !== 'concluido') {
                    $form_confirm = '<form method="post" action="' . admin_url('admin-post.php') . '"><input type="hidden" name="action" value="zxtec_confirm_ag"><input type="hidden" name="ag_id" value="' . get_the_ID() . '"><input type="submit" class="button" value="Confirmar"></form>';
                    $form_recusa = '<form method="post" action="' . admin_url('admin-post.php') . '"><input type="hidden" name="action" value="zxtec_recusar_ag"><input type="hidden" name="ag_id" value="' . get_the_ID() . '"><input type="text" name="ag_justificativa" placeholder="Justificativa" required><input type="submit" class="button" value="Recusar"></form>';
                }
                if (!$status) $status = 'pendente';
                $acoes = $form_confirm . $form_recusa . $form_concluir;
                echo '<tr><td>' . get_the_title($cliente) . '</td><td>' . esc_html($servico_nome) . '</td><td>' . esc_html($data) . '</td><td>' . $rota . '</td><td>' . esc_html($status) . '</td><td>' . $acoes . '</td></tr>';
            }
            echo '</tbody></table>';
            wp_reset_postdata();
        } else {
            echo '<p>Sem agendamentos.</p>';
        }
        echo '</div>';
    }

    public function render_colab_financeiro_page() {
        if (!current_user_can('read')) {
            wp_die('Acesso negado');
        }
        $args = array(
            'post_type' => 'zxtec_agendamento',
            'meta_key' => '_zxtec_ag_tecnico',
            'meta_value' => get_current_user_id(),
            'meta_query' => array(
                array('key' => '_zxtec_status', 'value' => 'concluido')
            ),
            'numberposts' => -1
        );
        $agendamentos = get_posts($args);
        echo '<div class="wrap"><h1>Financeiro do Colaborador</h1>';
        if ($agendamentos) {
            $total = 0;
            $mensal = array();
            echo '<table class="widefat"><thead><tr><th>Cliente</th><th>Serviço</th><th>Data</th><th>Comissão (R$)</th></tr></thead><tbody>';
            foreach ($agendamentos as $a) {
                $cliente = get_post_meta($a->ID, '_zxtec_ag_cliente', true);
                $servico = get_post_meta($a->ID, '_zxtec_ag_servico', true);
                $data = get_post_meta($a->ID, '_zxtec_ag_data', true);
                $comissao = get_post_meta($a->ID, '_zxtec_comissao', true);
                $total += floatval($comissao);
                $mes = substr($data, 0, 7);
                if (!isset($mensal[$mes])) $mensal[$mes] = 0;
                $mensal[$mes] += floatval($comissao);
                echo '<tr><td>' . esc_html(get_the_title($cliente)) . '</td><td>' . esc_html(get_the_title($servico)) . '</td><td>' . esc_html($data) . '</td><td>' . number_format($comissao,2,',','.') . '</td></tr>';
            }
            echo '<tr><th colspan="3" style="text-align:right">Total</th><th>' . number_format($total,2,',','.') . '</th></tr>';
            echo '</tbody></table>';
            if ($mensal) {
                echo '<h2>Comissões por Mês</h2><table class="widefat"><thead><tr><th>Mês</th><th>Total (R$)</th></tr></thead><tbody>';
                foreach ($mensal as $mes => $val) {
                    echo '<tr><td>' . esc_html($mes) . '</td><td>' . number_format($val,2,',','.') . '</td></tr>';
                }
                echo '</tbody></table>';
            }
            $desps = get_posts(array('post_type' => 'zxtec_despesa','author' => get_current_user_id(),'numberposts' => -1));
            $total_desp = 0;
            foreach ($desps as $d) {
                $total_desp += floatval(get_post_meta($d->ID, '_zxtec_dp_valor', true));
            }
            $saldo = $total - $total_desp;
            echo '<p><strong>Despesas:</strong> R$ ' . number_format($total_desp,2,',','.') . '</p>';
            echo '<p><strong>Saldo:</strong> R$ ' . number_format($saldo,2,',','.') . '</p>';
        } else {
            echo '<p>Sem serviços concluídos.</p>';
        }
        echo '</div>';
    }

    public function render_colab_despesas_page() {
        if (!current_user_can('read')) {
            wp_die('Acesso negado');
        }
        $args = array(
            'post_type' => 'zxtec_despesa',
            'author' => get_current_user_id(),
            'numberposts' => -1
        );
        $despesas = get_posts($args);
        echo '<div class="wrap"><h1>Minhas Despesas</h1>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="zxtec_add_despesa">';
        echo '<p><label>Valor (R$):<br><input type="number" step="0.01" name="zxtec_dp_valor"></label></p>';
        echo '<p><label>Data:<br><input type="date" name="zxtec_dp_data"></label></p>';
        echo '<p><label>Descrição:<br><textarea name="zxtec_dp_desc" rows="3"></textarea></label></p>';
        echo '<p><input type="submit" class="button button-primary" value="Adicionar"></p>';
        echo '</form>';
        if ($despesas) {
            $total = 0;
            echo '<h2>Despesas Registradas</h2><table class="widefat"><thead><tr><th>Data</th><th>Descrição</th><th>Valor</th></tr></thead><tbody>';
            foreach ($despesas as $d) {
                $valor = get_post_meta($d->ID, '_zxtec_dp_valor', true);
                $data = get_post_meta($d->ID, '_zxtec_dp_data', true);
                $desc = get_post_meta($d->ID, '_zxtec_dp_desc', true);
                $total += floatval($valor);
                echo '<tr><td>' . esc_html($data) . '</td><td>' . esc_html($desc) . '</td><td>' . number_format($valor,2,',','.') . '</td></tr>';
            }
            echo '<tr><th colspan="2" style="text-align:right">Total</th><th>' . number_format($total,2,',','.') . '</th></tr></tbody></table>';
            $url = admin_url('admin-post.php?action=zxtec_export_mydespesas_csv');
            echo '<p><a class="button" href="' . esc_url($url) . '">Exportar CSV</a></p>';
        }
        echo '</div>';
    }

    public function render_colab_historico_page() {
        if (!current_user_can('read')) {
            wp_die('Acesso negado');
        }
        $inicio = isset($_GET['inicio']) ? sanitize_text_field($_GET['inicio']) : '';
        $fim = isset($_GET['fim']) ? sanitize_text_field($_GET['fim']) : '';
        $meta = array(
            array('key' => '_zxtec_status', 'value' => 'concluido')
        );
        if ($inicio) {
            $meta[] = array('key' => '_zxtec_ag_data', 'value' => $inicio, 'compare' => '>=');
        }
        if ($fim) {
            $meta[] = array('key' => '_zxtec_ag_data', 'value' => $fim, 'compare' => '<=');
        }
        $args = array(
            'post_type' => 'zxtec_agendamento',
            'meta_key' => '_zxtec_ag_tecnico',
            'meta_value' => get_current_user_id(),
            'meta_query' => $meta,
            'numberposts' => -1
        );
        $agendamentos = get_posts($args);
        echo '<div class="wrap"><h1>Histórico de Serviços</h1>';
        echo '<form method="get"><input type="hidden" name="page" value="zxtec_colab_historico" />';
        echo '<p><label>Início:<br><input type="date" name="inicio" value="' . esc_attr($inicio) . '"></label></p>';
        echo '<p><label>Fim:<br><input type="date" name="fim" value="' . esc_attr($fim) . '"></label></p>';
        echo '<p><input type="submit" class="button" value="Filtrar"></p></form>';
        if ($agendamentos) {
            echo '<table class="widefat"><thead><tr><th>Cliente</th><th>Serviço</th><th>Data</th></tr></thead><tbody>';
            foreach ($agendamentos as $a) {
                $cliente = get_post_meta($a->ID, '_zxtec_ag_cliente', true);
                $servico = get_post_meta($a->ID, '_zxtec_ag_servico', true);
                $data = get_post_meta($a->ID, '_zxtec_ag_data', true);
                echo '<tr><td>' . esc_html(get_the_title($cliente)) . '</td><td>' . esc_html(get_the_title($servico)) . '</td><td>' . esc_html($data) . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Sem registros.</p>';
        }
        echo '</div>';
    }

    public function export_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
        $colaboradores = get_users(array('role' => 'zxtec_colaborador'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_financeiro.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Colaborador', 'Total (R$)'));
        foreach ($colaboradores as $colab) {
            $args = array(
                'post_type' => 'zxtec_agendamento',
                'meta_key' => '_zxtec_ag_tecnico',
                'meta_value' => $colab->ID,
                'numberposts' => -1
            );
            $posts = get_posts($args);
            $total = 0;
            foreach ($posts as $p) {
                $total += floatval(get_post_meta($p->ID, '_zxtec_comissao', true));
            }
            fputcsv($output, array($colab->display_name, number_format($total, 2, ',', '.')));
        }
        fclose($output);
        exit;
    }

    public function export_excel() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
        $colaboradores = get_users(array('role' => 'zxtec_colaborador'));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=relatorio_financeiro.xls');
        echo "Colaborador\tTotal (R$)\n";
        foreach ($colaboradores as $colab) {
            $args = array(
                'post_type' => 'zxtec_agendamento',
                'meta_key' => '_zxtec_ag_tecnico',
                'meta_value' => $colab->ID,
                'numberposts' => -1
            );
            $posts = get_posts($args);
            $total = 0;
            foreach ($posts as $p) {
                $total += floatval(get_post_meta($p->ID, '_zxtec_comissao', true));
            }
            echo $colab->display_name . "\t" . number_format($total, 2, ',', '.') . "\n";
        }
        exit;
    }

    public function export_pdf() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
        require_once plugin_dir_path(__FILE__) . 'lib/ZXTEC_PDF.php';
        $pdf = new ZXTEC_PDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(0,0,'Relatorio Financeiro');
        $colaboradores = get_users(array('role' => 'zxtec_colaborador'));
        foreach ($colaboradores as $colab) {
            $args = array(
                'post_type' => 'zxtec_agendamento',
                'meta_key' => '_zxtec_ag_tecnico',
                'meta_value' => $colab->ID,
                'numberposts' => -1
            );
            $posts = get_posts($args);
            $total = 0;
            foreach ($posts as $p) {
                $total += floatval(get_post_meta($p->ID, '_zxtec_comissao', true));
            }
            $pdf->Cell(0,0,$colab->display_name . ' - ' . number_format($total,2,',','.'));
        }
        $pdf->Output('relatorio_financeiro.pdf');
    }

    public function export_historico_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
        $inicio = isset($_GET['inicio']) ? sanitize_text_field($_GET['inicio']) : '';
        $fim    = isset($_GET['fim']) ? sanitize_text_field($_GET['fim']) : '';
        $tec    = isset($_GET['tecnico']) ? intval($_GET['tecnico']) : 0;
        $meta = array(
            array('key' => '_zxtec_status', 'value' => 'concluido')
        );
        if ($inicio) $meta[] = array('key' => '_zxtec_ag_data', 'value' => $inicio, 'compare' => '>=');
        if ($fim)    $meta[] = array('key' => '_zxtec_ag_data', 'value' => $fim, 'compare' => '<=');
        if ($tec)    $meta[] = array('key' => '_zxtec_ag_tecnico', 'value' => $tec);
        $args = array(
            'post_type' => 'zxtec_agendamento',
            'meta_query' => $meta,
            'numberposts' => -1
        );
        $agendamentos = get_posts($args);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=historico.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Cliente','Serviço','Técnico','Data'));
        foreach ($agendamentos as $a) {
            $cliente = get_post_meta($a->ID, '_zxtec_ag_cliente', true);
            $servico = get_post_meta($a->ID, '_zxtec_ag_servico', true);
            $tecnico = get_post_meta($a->ID, '_zxtec_ag_tecnico', true);
            $data = get_post_meta($a->ID, '_zxtec_ag_data', true);
            fputcsv($output, array(get_the_title($cliente), get_the_title($servico), get_userdata($tecnico)->display_name, $data));
        }
        fclose($output);
        exit;
    }

    public function export_despesas_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=despesas.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Colaborador','Data','Descricao','Valor (R$)'));
        $despesas = get_posts(array('post_type' => 'zxtec_despesa','numberposts' => -1));
        foreach ($despesas as $d) {
            $autor = get_userdata($d->post_author);
            $valor = get_post_meta($d->ID, '_zxtec_dp_valor', true);
            $data = get_post_meta($d->ID, '_zxtec_dp_data', true);
            $desc = get_post_meta($d->ID, '_zxtec_dp_desc', true);
            fputcsv($output, array($autor->display_name, $data, $desc, number_format($valor,2,',','.')));
        }
        fclose($output);
        exit;
    }

    public function export_mydespesas_csv() {
        if (!current_user_can('read')) {
            wp_die('Acesso negado');
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=minhas_despesas.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Data','Descricao','Valor (R$)'));
        $despesas = get_posts(array(
            'post_type' => 'zxtec_despesa',
            'author' => get_current_user_id(),
            'numberposts' => -1
        ));
        foreach ($despesas as $d) {
            $valor = get_post_meta($d->ID, '_zxtec_dp_valor', true);
            $data = get_post_meta($d->ID, '_zxtec_dp_data', true);
            $desc = get_post_meta($d->ID, '_zxtec_dp_desc', true);
            fputcsv($output, array($data, $desc, number_format($valor,2,',','.')));
        }
        fclose($output);
        exit;
    }

    public function user_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) return;
        $lat = get_user_meta($user->ID, 'zxtec_lat', true);
        $lng = get_user_meta($user->ID, 'zxtec_lng', true);
        $esp = get_user_meta($user->ID, 'zxtec_specialty', true);
        echo '<h2>Dados do Colaborador ZX Tec</h2>';
        echo '<table class="form-table"><tr><th><label for="zxtec_lat">Latitude</label></th><td><input type="text" name="zxtec_lat" value="' . esc_attr($lat) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="zxtec_lng">Longitude</label></th><td><input type="text" name="zxtec_lng" value="' . esc_attr($lng) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="zxtec_specialty">Especialidade</label></th><td><input type="text" name="zxtec_specialty" value="' . esc_attr($esp) . '" class="regular-text" /></td></tr></table>';
    }

    public function save_user_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) return false;
        if (isset($_POST['zxtec_lat'])) update_user_meta($user_id, 'zxtec_lat', sanitize_text_field($_POST['zxtec_lat']));
        if (isset($_POST['zxtec_lng'])) update_user_meta($user_id, 'zxtec_lng', sanitize_text_field($_POST['zxtec_lng']));
        if (isset($_POST['zxtec_specialty'])) update_user_meta($user_id, 'zxtec_specialty', sanitize_text_field($_POST['zxtec_specialty']));
    }

    private function auto_assign_tecnico($servico_id, $cliente_id) {
        $esp = get_post_meta($servico_id, '_zxtec_categoria', true);
        $preco = get_post_meta($servico_id, '_zxtec_preco', true);
        $client_lat = get_post_meta($cliente_id, '_zxtec_lat', true);
        $client_lng = get_post_meta($cliente_id, '_zxtec_lng', true);
        $users = get_users(array('role' => 'zxtec_colaborador', 'meta_key' => 'zxtec_specialty', 'meta_value' => $esp));
        if (!$users) {
            $users = get_users(array('role' => 'zxtec_colaborador'));
        }
        $best = 0;
        $best_cost = PHP_FLOAT_MAX;
        foreach ($users as $u) {
            $lat = get_user_meta($u->ID, 'zxtec_lat', true);
            $lng = get_user_meta($u->ID, 'zxtec_lng', true);
            if ($client_lat && $client_lng && $lat && $lng) {
                $dist = $this->calc_distance($client_lat, $client_lng, $lat, $lng);
            } else {
                $dist = 99999;
            }
            $posts = get_posts(array(
                'post_type' => 'zxtec_agendamento',
                'meta_key' => '_zxtec_ag_tecnico',
                'meta_value' => $u->ID,
                'numberposts' => -1
            ));
            $num = count($posts);
            $cost = $dist + ($num * 10) + floatval($preco);
            if ($cost < $best_cost) {
                $best_cost = $cost;
                $best = $u->ID;
            }
        }
        return $best;
    }

    private function calc_distance($lat1, $lon1, $lat2, $lon2) {
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        $dlon = $lon2 - $lon1;
        $dlat = $lat2 - $lat1;
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $r = 6371; // km
        return $r * $c;
    }

    private function auto_schedule_time($tecnico_id) {
        $posts = get_posts(array(
            'post_type' => 'zxtec_agendamento',
            'meta_query' => array(
                array(
                    'key' => '_zxtec_ag_tecnico',
                    'value' => $tecnico_id,
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_zxtec_ag_data',
            'order' => 'DESC',
            'numberposts' => 1,
            'post_status' => 'any'
        ));
        if ($posts) {
            $last = get_post_meta($posts[0]->ID, '_zxtec_ag_data', true);
            $time = strtotime($last . ' +1 day');
        } else {
            $time = strtotime('tomorrow 09:00');
        }
        return date('Y-m-d H:i', $time);
    }

    public function confirm_agendamento() {
        if (!current_user_can('read')) wp_die('Acesso negado');
        $id = intval($_POST['ag_id']);
        update_post_meta($id, '_zxtec_status', 'confirmado');
        $admin = get_option('admin_email');
        wp_mail($admin, 'Agendamento confirmado', 'O agendamento #' . $id . ' foi confirmado.');
        wp_redirect(wp_get_referer());
        exit;
    }

    public function recusar_agendamento() {
        if (!current_user_can('read')) wp_die('Acesso negado');
        $id = intval($_POST['ag_id']);
        $jus = sanitize_text_field($_POST['ag_justificativa']);
        update_post_meta($id, '_zxtec_status', 'recusado');
        update_post_meta($id, '_zxtec_justificativa', $jus);
        $admin = get_option('admin_email');
        wp_mail($admin, 'Agendamento recusado', 'O agendamento #' . $id . " foi recusado:\n" . $jus);
        wp_redirect(wp_get_referer());
        exit;
    }

    public function concluir_agendamento() {
        if (!current_user_can('read')) wp_die('Acesso negado');
        $id = intval($_POST['ag_id']);
        update_post_meta($id, '_zxtec_status', 'concluido');
        $admin = get_option('admin_email');
        wp_mail($admin, 'Agendamento concluido', 'O agendamento #' . $id . ' foi concluido.');
        wp_redirect(wp_get_referer());
        exit;
    }

    public function add_despesa() {
        if (!current_user_can('read')) wp_die('Acesso negado');
        $valor = floatval($_POST['zxtec_dp_valor']);
        $data = sanitize_text_field($_POST['zxtec_dp_data']);
        $desc = sanitize_textarea_field($_POST['zxtec_dp_desc']);
        $post_id = wp_insert_post(array(
            'post_type' => 'zxtec_despesa',
            'post_title' => $desc,
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ));
        if ($post_id) {
            update_post_meta($post_id, '_zxtec_dp_valor', $valor);
            update_post_meta($post_id, '_zxtec_dp_data', $data);
            update_post_meta($post_id, '_zxtec_dp_desc', $desc);
        }
        wp_redirect(wp_get_referer());
        exit;
    }
}

new ZXTEC_Intranet();
