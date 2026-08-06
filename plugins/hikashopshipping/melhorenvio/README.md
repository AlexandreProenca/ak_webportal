# HikaShop — Melhor Envio

Plugin de frete para Joomla 5/HikaShop com cotação e ciclo completo de etiquetas comerciais: cotar, inserir no carrinho, comprar, gerar, imprimir, rastrear e cancelar. A emissão só começa quando o pedido está pago e possui chave NF-e válida.

## Instalação e configuração

Instale o ZIP pelo Joomla e habilite `HikaShop - Frete Melhor Envio`. Em **HikaShop > Sistema > Métodos de envio**, crie o método e comece em `sandbox`.

Preencha Client ID, e-mail técnico, serviços permitidos (por exemplo, `1,2,3`), status pagos (`confirmed`) e os dados fiscais do remetente: CNPJ, IE e endereço completo. Prefira definir o segredo fora do banco, por exemplo `MELHOR_ENVIO_CLIENT_SECRET`, e informe esse nome em “variável de ambiente”. O Access Token e o Refresh Token são criptografados nas tabelas do plugin.

Os produtos físicos precisam de SKU, preço unitário, peso e dimensões. No HikaShop, confirme também os campos de endereço usados para CPF/CNPJ, número e bairro. Crie um campo personalizado de pedido para a chave NF-e (padrão: `order_nf_key`, exatamente 44 dígitos).

Cadastre como callback OAuth no aplicativo:

```text
https://SEU-DOMINIO/index.php?option=com_ajax&plugin=melhorenvio&group=hikashopshipping&format=json&action=callback&shipping_id=ID
```

Na configuração do método de frete, clique em **Autorizar com Melhor Envio**. O plugin cria um estado de uso único, válido por 15 minutos, e abre a autorização no Melhor Envio. Isso funciona mesmo quando o Joomla mantém sessões separadas para o administrador e para o site.

Cadastre o webhook com o segredo do aplicativo:

```text
https://SEU-DOMINIO/index.php?option=com_ajax&plugin=melhorenvio&group=hikashopshipping&format=json&action=webhook&shipping_id=ID
```

## Operação

O snapshot da cotação é gravado no pedido. Após pagamento + NF-e, o plugin persiste cada ID externo antes de avançar por `cart → released → generated`; reprocessamentos retomam do estágio salvo. Se a conexão cair durante a criação, o estado vira `uncertain_cart` e novas compras são bloqueadas: confira o carrinho do Melhor Envio e use `link` com `order_id`, `label_id` e `package_index` antes de reprocessar. Isso evita etiquetas duplicadas.

As ações administrativas `process`, `tracking`, `label`, `cancel` e `link` usam POST, exigem `core.manage` no HikaShop e token CSRF. `label` aceita `pdf`, `zpl` ou `jpeg`; `cancel` primeiro consulta se a etiqueta ainda é cancelável.

As tabelas `#__ak_me_*` preservam credenciais criptografadas, auditoria, eventos e etiquetas. Elas não são removidas na desinstalação para manter o histórico fiscal. Nunca teste compra real antes de validar OAuth, cotação, NF-e, etiqueta e webhook no Sandbox.
