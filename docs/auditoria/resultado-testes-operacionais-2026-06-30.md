# Resultado dos Testes Operacionais

Data: 2026-06-30

## Segurança antes das alterações

- Backup do banco criado.
- Backup dos arquivos criado.
- Alterações estruturais feitas por migration incremental:
  - `2026_06_30_151900_add_restaurant_operations_fields.php`
- Não foi usado `migrate:fresh` após as alterações.

## Testes executados

| Fluxo | Resultado |
| --- | --- |
| Login | OK |
| Permissões/admin | OK |
| Cadastro de produtos/cardápio | OK |
| Fichas técnicas | OK |
| Mesas/comandas | OK |
| Abertura de caixa | OK |
| Pedido modo mesa | OK |
| Envio para produção | OK |
| Impressão cozinha | OK |
| Status recebido -> em preparo -> pronto -> entregue -> pago | OK |
| Pagamento em `payments` | OK |
| Movimentação em `cash_movements` | OK |
| Fechamento de caixa | OK |
| Baixa automática de estoque por ficha técnica | OK |
| Cancelamento com reversão de estoque | OK |
| Cancelamento com reversão financeira | OK |
| Notificações | OK |
| Logs/auditoria | OK |
| Relatórios com filtro de mesa e tipo | OK |
| Testes automatizados `php artisan test` | OK |

## Evidências rápidas

- Pedido de teste criado: `PD2026063019222816`
- Estoque baixado ao vender PF Executivo:
  - Arroz cozido: `25 -> 24.82`
  - Feijão cozido: `18 -> 17.86`
  - Carne grelhada: `12 -> 11.78`
- Estoque revertido no cancelamento:
  - Arroz cozido: `24.82 -> 25`
  - Feijão cozido: `17.86 -> 18`
  - Carne grelhada: `11.78 -> 12`
- Registros após teste:
  - `logs`: 12
  - `notifications`: 3
  - `orders`: 1
  - `stock_movements`: 6

## Observação

O pedido de teste foi mantido no banco para preservar evidência e evitar limpeza destrutiva. Em produção, testes desse tipo devem ser executados em ambiente de homologação.
