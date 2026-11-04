# Auditoria de Arquitetura - Restaurante Pro

Data: 2026-06-30

## Backup antes da alteração

- Banco: `storage/app/backups/manual/database-before-restaurant-complement-20260630-151824.sqlite`
- Arquivos: `storage/app/backups/manual/files-before-restaurant-complement-20260630-151824.tar.gz`

## Estrutura existente mapeada

### Tabelas de domínio

- Autenticação/permissões: `users`, `profiles`, `permissions`, `profile_permissions`, `sessions`, `password_reset_tokens`
- Empresa/filiais: `companies`, `branches`
- Cadastros: `products`, `categories`, `brands`, `suppliers`, `customers`
- Restaurante/cardápio: `recipes`, `recipe_items`, `tables`
- Pedidos: `orders`, `order_items`
- Estoque: `stock_movements`, `inventory`, `inventory_items`
- Compras: `purchases`, `purchase_items`
- Financeiro/caixa: `payments`, `cash_registers`, `cash_movements`, `accounts_payable`, `accounts_receivable`
- Reversões: `cancellations`, `returns`, `return_items`
- Plataforma: `notifications`, `settings`, `backups`, `logs`

### Controllers e serviços

- `AuthController`: login, logout e senhas
- `DashboardController`: indicadores
- `ModuleController`: CRUDs reaproveitados
- `PosController`: pedidos/PDV
- `CashRegisterController`: caixa
- `PurchaseController`: compras com entrada no estoque
- `StockController`: movimentações de estoque
- `InventoryController`: inventário
- `ReportsController`: relatórios
- `BackupController`: backups
- `SettingsController`: configurações
- `RestaurantStockService`: baixa/reversão de estoque por produto ou ficha técnica

### Views

- `layouts/app.blade.php`: navegação e layout
- `pos/create.blade.php`: pedido/PDV
- `pos/receipt.blade.php`: comprovante
- `modules/*`: CRUDs, caixa, compras, estoque, inventário
- `reports/index.blade.php`: relatórios
- `settings/edit.blade.php`: configurações
- `backups/index.blade.php`: backups

## Duplicidades identificadas

- Produtos/cardápio/ingredientes já estão consolidados em `products`; não criar tabela separada de ingredientes.
- Pedidos/vendas/comandas já usam `orders` e `order_items`; não recriar `sales`.
- Pagamentos devem continuar em `payments` e `cash_movements`; não duplicar valores em pedidos além dos totais necessários.
- Clientes delivery devem continuar em `customers`.
- Auditoria deve continuar em `logs`.
- Alertas devem continuar em `notifications`.

## Lacunas encontradas

- Modos explícitos faltantes: `individual_tab` e `internal_consumption`.
- Separação de impressão por setor: cozinha, bar, caixa e comprovante do cliente.
- Tela operacional de status em tempo real para produção/entrega/pagamento.
- Campos de status mais descritivos: recebido, em preparo, pronto, entregue, pago e cancelado.

## Estratégia de refatoração segura

- Criar migrations aditivas com `Schema::table`, sem apagar dados.
- Reaproveitar `orders`, `order_items`, `products`, `tables`, `payments`, `cash_movements`, `notifications` e `logs`.
- Atualizar controllers e views para usar os novos campos.
- Testar login, permissões, cadastros, pedidos, produção, pagamento, caixa, estoque, cancelamento e relatórios após alterações.
