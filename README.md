# Restaurante Pro

Sistema Laravel para restaurante com pedidos, mesas/comandas, delivery, cardápio, fichas técnicas, estoque de insumos, compras, caixa, financeiro, relatórios, auditoria e backups.

## Acesso inicial

- URL local: `http://127.0.0.1:8010`
- E-mail: `admin@sistema.local`
- Senha: `Admin@123456`

Troque a senha do administrador no primeiro acesso.

## Rodar em desenvolvimento

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8010
```

## Arquitetura consolidada

- `products`: cadastro central de produtos, cardápio e insumos.
- `product_type`: `resale`, `ingredient`, `ready_dish`, `drink`, `combo`, `additional`.
- `recipes` e `recipe_items`: ficha técnica de pratos e combos.
- `orders` e `order_items`: fluxo único de pedido, produção, entrega, pagamento e fechamento.
- `tables`: mesas e comandas, com suporte a agrupamento/transferência via relacionamento.
- `payments` e `cash_movements`: movimentações financeiras centralizadas.
- `customers`: clientes e dados de delivery no mesmo cadastro.
- `stock_movements`: entradas, saídas, perdas, ajustes, consumo por receita e reversões.
- `notifications`: alertas centralizados por tipo.
- `logs`: auditoria com usuário, ação, IP, dispositivo e registro alterado.

## Operação do restaurante

Modos suportados:

- Mesa
- Comanda individual
- Balcão
- Retirada
- Delivery
- Consumo interno

Status em tempo real:

- Pedido recebido
- Em preparo
- Pronto
- Entregue
- Pago
- Cancelado

Impressão por setor:

- Cozinha
- Bar
- Caixa
- Comprovante do cliente

Use a tela `Produção` para acompanhar pedidos por setor, mudar status e imprimir comandas.

## Regras de negócio

- Vender prato pronto ou combo baixa os insumos pela ficha técnica.
- Vender bebida, adicional, ingrediente ou produto de revenda baixa o próprio estoque.
- Cancelar pedido reverte estoque, pagamentos, contas a receber e caixa.
- Registrar compra gera entrada automática no estoque e pode gerar contas a pagar.
- Pedido de mesa ocupa a mesa; cancelamento libera a mesa.
- Pagamentos sempre registram em `payments` e `cash_movements`.
- Delivery reutiliza `customers`, com endereço, referência, taxa e observações.

## Módulos

- Autenticação, usuários e permissões.
- Dashboard com pedidos, caixa, estoque, contas vencidas e mesas.
- Produtos/Cardápio.
- Fichas técnicas e ingredientes da ficha.
- Mesas e comandas.
- Clientes/delivery.
- Compras e fornecedores.
- Estoque e inventário.
- Pedidos/PDV.
- Caixa e pagamentos.
- Financeiro.
- Relatórios com filtros por período, mesa, usuário/garçom, delivery e caixa.
- Notificações, configurações, backups e auditoria.

## Backup automático

Backup manual pela tela `Backups` ou via comando:

```bash
php artisan backup:database
```

Para produção, configure o scheduler do Laravel no cron do servidor:

```bash
* * * * * cd /caminho/do/sistema-comercial && php artisan schedule:run >> /dev/null 2>&1
```

O agendamento padrão roda diariamente às `02:00`.

## Produção

```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Recomendações:

- Defina `APP_ENV=production` e `APP_DEBUG=false`.
- Use HTTPS e cookies seguros.
- Configure MySQL/PostgreSQL para operação multiusuário intensa.
- Configure e-mail real para recuperação de senha.
- Proteja backups e arquivos privados.
