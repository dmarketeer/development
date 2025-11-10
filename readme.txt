=== Oportunidades ===
Contributors: equipa-oportunidades
Tags: opportunities, pipeline, procurement
Requires at least: 6.3
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin responsável por receber datasets de oportunidades provenientes de um pipeline externo, armazenando os registos numa tabela optimizada no WordPress e disponibilizando-os no frontend.

== Descrição ==

O plugin **Oportunidades** foi concebido para importar dados de oportunidades a partir de Google Spreadsheets. O plugin disponibiliza:

* Integração com Google Sheets API para importação automática de dados.
* Endpoint REST autenticado para ingestão directa de JSON.
* Upload manual de ficheiros JSON/CSV com esquema compatível.
* Persistência numa tabela customizada com histórico e deduplicação por hash.
* Shortcode, bloco Gutenberg e página pública pré-configurada que consultam exclusivamente a base local.
* Interface administrativa com filtros, estatísticas, exportação via tabela dinâmica e configuração.
* Agendamentos automáticos para sincronização com Google Sheets e envio de resumos por e-mail.

== Instalação ==

1. Copie a pasta `oportunidades` para o directório `wp-content/plugins/` do seu WordPress.
2. No painel `Plugins`, active **Oportunidades**.
3. Após a activação, configure a integração Google Sheets em `Oportunidades > Integração Google Sheets`.
4. Configure o ID da planilha, o intervalo de dados (range) e a API Key do Google Cloud Console.
5. Certifique-se de que a Google Sheets API está habilitada no seu projeto do Google Cloud.
6. A planilha deve ter permissões de visualização pública ou estar configurada adequadamente.
7. Opcionalmente, configure destinatários de e-mail para resumos diários.

== Configuração ==

Na página de definições, pode:

* Configurar a integração com Google Sheets (ID da planilha, range, API Key).
* Gerar/definir o token de API para importações REST.
* Definir intervalo de sincronização automática.
* Configurar filtros predefinidos e campos adicionais.
* Realizar upload manual de datasets JSON/CSV.
* Sincronizar manualmente com Google Sheets.
* Consultar resumo da última ingestão.

== Endpoint REST ==

`POST /wp-json/oportunidades/v1/import`

Headers:

* `Authorization: Bearer <token>`

Body (JSON):

```
{
  "schema_version": "1.0",
  "oportunidades": [
    {
      "identificador": "DR-2024-001",
      "titulo": "Reabilitação de fachada",
      "resumo": "Intervenção em fachada principal",
      "entidade_adjudicante": "Município X",
      "valor_normalizado": 1200000,
      "prazo": "2024-05-20",
      "url": "https://dre.pt/...",
      "categorias": ["Reabilitação"],
      "filtros": ["Fachadas", "LSF"]
    }
  ]
}
```

== Shortcode ==

`[oportunidades categoria="Reabilitação" distrito="Lisboa" limite="10"]`

== Bloco Gutenberg ==

Procure por "Lista de Oportunidades" ao editar uma página/bloco. Ajuste os atributos na barra lateral.

== Página Pública ==

Aquando da activação, o plugin cria uma página "Oportunidades" com o shortcode inserido. Pode editar o conteúdo livremente mantendo o shortcode/bloco.

== Cron e E-mails ==

* Evento `oportunidades_sheets_sync` executa de hora a hora para sincronizar com Google Sheets, respeitando o intervalo configurado.
* Evento `oportunidades_process_local_file` executa de hora a hora para reprocessar ficheiros locais, respeitando o intervalo configurado.
* Evento `oportunidades_send_digest` envia diariamente às 09:00 UTC um resumo via `wp_mail`.

== Segurança ==

* Token partilhado armazenado em opção protegida.
* Validação de nonce e capabilities no painel.
* Dados escapados antes de serem apresentados no frontend.

== Testes ==

Inclui uma suite básica PHPUnit em `tests/`. Execute com `wp-env run composer test` ou o seu ambiente de testes preferido.
