 (cd "$(git rev-parse --show-toplevel)" && git apply --3way <<'EOF' 
diff --git a//dev/null b/zxtec-intranet/README.txt
index 0000000000000000000000000000000000000000..4c2ad37fa05e93ae0d8315dbe698a1c830258ea2 100644
--- a//dev/null
+++ b/zxtec-intranet/README.txt
@@ -0,0 +1,60 @@
+=== ZX Tec - Intranet de Servicos e Colaboradores ===
+Contributors: zxtec
+Tags: intranet, crm, services
+Requires at least: 6.0
+Tested up to: 6.3
+Stable tag: 1.17.0
+License: GPLv2 or later
+License URI: https://www.gnu.org/licenses/gpl-2.0.html
+
+ZX Tec - Intranet de Serviços oferece painel administrativo, dashboard de colaboradores, gerenciamento de clientes e agendamentos.
+
+== Installation ==
+1. Copie a pasta `zxtec-intranet` para o diretório `wp-content/plugins/`.
+2. Ative o plugin no painel "Plugins" do WordPress.
+3. Acesse "ZX Tec Admin" para cadastrar clientes, serviços e agendamentos.
+4. Colaboradores podem acessar o painel "ZX Tec Colaborador" após receberem a função correspondente.
+5. Para remover completamente a função de colaborador, desinstale o plugin pelo painel do WordPress.
+
+== Usage ==
+* Utilize o menu **ZX Tec Admin** para gerenciar clientes, serviços, agendamentos, contratos e despesas.
+* Os colaboradores acessam o menu **ZX Tec Colaborador** para visualizar e confirmar agendamentos, registrar despesas e consultar relatórios.
+* Para gerar o arquivo ZIP do plugin manualmente, execute `zip -r zxtec-intranet.zip zxtec-intranet` no terminal antes de enviar para o WordPress.
+* O plugin é traduzível utilizando o text domain `zxtec-intranet`.
+
+== Changelog ==
+* 1.17.0
+* Ajustes finais e documentação
+* 1.16.0
+* Remoção da role ao desinstalar o plugin
+* 1.15.0
+* Exportação de despesas do colaborador em CSV
+* 1.14.0
+* Filtros no histórico de instalações e exportação CSV
+* 1.13.0
+* Histórico filtrável e relatório de comissões mensal no painel do colaborador
+* 1.12.0
+* Relatório de despesas para administrador
+* 1.11.0
+* Registro de despesas do colaborador com cálculo de saldo
+* 1.10.0
+* Finalização de agendamentos pelo colaborador
+* 1.9.0
+* Mapa de disponibilidade dos colaboradores no painel admin
+* 1.8.0
+* Painel financeiro para colaborador
+* 1.7.0
+* Agendamento automatizado e histórico de instalações
+* 1.6.0
+* Finalização de agendamentos com envio de e-mail
+* 1.5.0
+* Exportação em Excel e PDF de relatórios financeiros
+* Atribuição automática considerando distância e custo
+* 1.4.0
+* Gestão de contratos com listagem de ativos
+* 1.3.0
+* Confirmação ou recusa de agendamentos pelo colaborador
+* Atribuição automática de técnico conforme especialidade
+* 1.2.0
+* Adicionada exportação CSV para relatórios financeiros
+* Notificação por e-mail para novos agendamentos
 
EOF
)
