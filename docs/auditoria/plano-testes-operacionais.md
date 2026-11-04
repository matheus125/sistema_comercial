# Plano de Testes Operacionais

Executar após alterações estruturais ou regras de negócio.

## Checklist obrigatório

- Login: acessar com `admin@sistema.local`.
- Permissões: abrir telas protegidas e validar perfil administrador.
- Cadastro de produtos: acessar Produtos/Cardápio e conferir tipos e setor de impressão.
- Cadastro de cardápio: acessar Fichas técnicas e Ingredientes da ficha.
- Abertura de mesa: criar pedido modo `Mesa` ou `Comanda individual`.
- Pedido: registrar itens no PDV.
- Envio para cozinha/bar: abrir Produção e filtrar setor.
- Status em tempo real: recebido, em preparo, pronto, entregue, pago e cancelado.
- Pagamento: conferir registros em `payments` e `cash_movements`.
- Fechamento de caixa: informar valor conferido e fechar.
- Baixa automática de estoque: vender prato com ficha técnica e verificar `stock_movements`.
- Cancelamento: cancelar pedido e verificar reversão de estoque/financeiro/log.
- Relatórios: filtrar por período, mesa, usuário/garçom e modo de operação.

## Observações

- Não usar `migrate:fresh` em produção.
- Antes de migrations estruturais, gerar backup de banco e arquivos.
- Novas estruturas devem reaproveitar tabelas existentes quando possível.
