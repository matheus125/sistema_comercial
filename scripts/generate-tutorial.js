import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const docsDir = path.join(root, 'docs', 'tutorial');
const shotsDir = path.join(docsDir, 'screenshots');
const baseUrl = process.env.APP_URL || 'http://127.0.0.1:8010';

fs.mkdirSync(shotsDir, { recursive: true });

const browser = await chromium.launch({
  executablePath: '/usr/bin/google-chrome',
  headless: true,
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const page = await browser.newPage({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });

async function shot(name, url, waitFor = 'h1') {
  await page.goto(`${baseUrl}${url}`, { waitUntil: 'networkidle' });
  await page.locator(waitFor).first().waitFor({ timeout: 10000 });
  await page.screenshot({ path: path.join(shotsDir, `${name}.png`), fullPage: true });
}

async function login() {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'admin@sistema.local');
  await page.fill('input[name="password"]', 'Admin@123456');
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 10000 }),
    page.click('button:has-text("Entrar")'),
  ]);
}

async function ensureOpenCash() {
  await page.goto(`${baseUrl}/caixa`, { waitUntil: 'networkidle' });
  const alreadyOpen = await page.locator('form[action*="/fechar"]').count();
  if (alreadyOpen > 0) {
    return;
  }

  await page.fill('input[name="opening_amount"]', '100');
  await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button:has-text("Abrir")'),
  ]);
}

async function createSampleOrder() {
  await ensureOpenCash();
  await page.goto(`${baseUrl}/pdv`, { waitUntil: 'networkidle' });
  await page.selectOption('select[name="order_type"]', 'table');
  await page.selectOption('select[name="table_id"]', { index: 1 });
  await page.fill('input[name="command_code"]', 'MESA-01');
  await page.fill('input[name="cash_received"]', '50');
  await page.locator('select[name="products[]"]').first().selectOption(
    await page.locator('select[name="products[]"] option', { hasText: 'PF Executivo' }).first().getAttribute('value')
  );
  await Promise.all([
    page.waitForURL('**/pedidos/*/comprovante', { timeout: 10000 }),
    page.click('button:has-text("Finalizar pedido")'),
  ]);
}

await login();

await shot('01-dashboard', '/dashboard');
await shot('02-produtos-cardapio', '/cadastros/products');
await shot('03-fichas-tecnicas', '/cadastros/recipes');
await shot('04-ingredientes-ficha', '/cadastros/recipe-items');
await shot('05-mesas-comandas', '/cadastros/tables');
await shot('06-clientes-delivery', '/cadastros/customers');
await shot('07-pdv-pedidos', '/pdv');
await ensureOpenCash();
await shot('08-caixa', '/caixa');
await createSampleOrder();
await page.screenshot({ path: path.join(shotsDir, '09-comprovante.png'), fullPage: true });
await shot('10-estoque', '/estoque');
await shot('11-compras', '/compras');
await shot('12-relatorios', '/relatorios');
await shot('13-configuracoes', '/configuracoes');

const html = `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Tutorial Restaurante Pro</title>
  <style>
    @page { size: A4; margin: 14mm; }
    body { font-family: Arial, sans-serif; color: #1f2937; line-height: 1.45; margin: 0; }
    h1 { font-size: 30px; margin: 0 0 8px; }
    h2 { font-size: 22px; margin: 28px 0 8px; color: #0f766e; }
    h3 { font-size: 16px; margin: 14px 0 6px; }
    p, li { font-size: 12.5px; }
    .cover { page-break-after: always; padding-top: 120px; }
    .muted { color: #667085; }
    .box { border: 1px solid #d8dde6; background: #f6f7f9; border-radius: 8px; padding: 12px; margin: 12px 0; }
    .shot { width: 100%; border: 1px solid #d8dde6; border-radius: 6px; margin: 10px 0 18px; }
    .page { page-break-before: always; }
    code { background: #eef2f6; padding: 2px 5px; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #d8dde6; padding: 7px; font-size: 12px; text-align: left; }
    th { background: #f2f4f7; }
  </style>
</head>
<body>
  <section class="cover">
    <h1>Tutorial de Uso - Restaurante Pro</h1>
    <p class="muted">Guia operacional com telas reais do sistema.</p>
    <div class="box">
      <p><strong>Acesso local:</strong> ${baseUrl}</p>
      <p><strong>Usuário inicial:</strong> admin@sistema.local</p>
      <p><strong>Senha inicial:</strong> Admin@123456</p>
    </div>
    <p>Este tutorial mostra o fluxo principal para usar o sistema no dia a dia de um restaurante: cadastrar cardápio e insumos, configurar fichas técnicas, abrir caixa, registrar pedidos, controlar mesas, compras, estoque e relatórios.</p>
  </section>

  <h2>1. Dashboard</h2>
  <p>Use o dashboard para acompanhar pedidos do dia, pedidos do mês, lucro estimado, formas de pagamento, caixas, estoque baixo, contas vencidas, mesas ocupadas e pedidos delivery.</p>
  <img class="shot" src="screenshots/01-dashboard.png">

  <section class="page">
    <h2>2. Produtos, Cardápio e Insumos</h2>
    <p>O sistema usa um cadastro central para produtos, cardápio e ingredientes. No campo <code>Tipo de produto</code>, escolha entre produto de revenda, ingrediente, prato pronto, bebida, combo ou adicional.</p>
    <ul>
      <li><strong>Ingrediente:</strong> usado em ficha técnica e controlado no estoque.</li>
      <li><strong>Prato pronto/Combo:</strong> vendido no PDV e baixa os insumos da ficha técnica.</li>
      <li><strong>Bebida/Adicional/Revenda:</strong> baixa diretamente o próprio estoque.</li>
    </ul>
    <img class="shot" src="screenshots/02-produtos-cardapio.png">
  </section>

  <section class="page">
    <h2>3. Fichas Técnicas</h2>
    <p>Cadastre a ficha técnica para pratos e combos. A ficha define quais insumos serão baixados quando o item for vendido.</p>
    <img class="shot" src="screenshots/03-fichas-tecnicas.png">
    <h3>Ingredientes da ficha</h3>
    <p>Em "Ingredientes da ficha", informe o insumo, quantidade e unidade. Exemplo: PF Executivo usa arroz, feijão e carne.</p>
    <img class="shot" src="screenshots/04-ingredientes-ficha.png">
  </section>

  <section class="page">
    <h2>4. Mesas e Comandas</h2>
    <p>Cadastre mesas do salão. Um pedido do tipo mesa pode usar uma comanda, ocupar a mesa e depois liberar a mesa no cancelamento ou fechamento do fluxo.</p>
    <img class="shot" src="screenshots/05-mesas-comandas.png">
  </section>

  <section class="page">
    <h2>5. Clientes e Delivery</h2>
    <p>O cadastro de clientes também atende delivery. Use endereço, referência, taxa de entrega padrão e observações de entrega no mesmo registro, sem duplicidade de tabela.</p>
    <img class="shot" src="screenshots/06-clientes-delivery.png">
  </section>

  <section class="page">
    <h2>6. Abrir Caixa</h2>
    <p>Antes de registrar pagamento, abra um caixa com o valor inicial. O sistema impede operação de pagamento sem caixa aberto.</p>
    <ol>
      <li>Acesse <strong>Caixa</strong>.</li>
      <li>Informe o valor inicial.</li>
      <li>Clique em <strong>Abrir</strong>.</li>
    </ol>
    <img class="shot" src="screenshots/08-caixa.png">
  </section>

  <section class="page">
    <h2>7. Registrar Pedido no PDV</h2>
    <p>No PDV, escolha o tipo de pedido: balcão, mesa, delivery ou retirada. Depois selecione cliente, mesa/comanda quando necessário, itens do cardápio, descontos e forma de pagamento.</p>
    <ol>
      <li>Selecione o tipo do pedido.</li>
      <li>Escolha mesa e comanda se for atendimento de salão.</li>
      <li>Adicione os itens do cardápio.</li>
      <li>Informe pagamento, taxa de entrega quando houver e valor recebido.</li>
      <li>Clique em <strong>Finalizar pedido</strong>.</li>
    </ol>
    <img class="shot" src="screenshots/07-pdv-pedidos.png">
  </section>

  <section class="page">
    <h2>8. Comprovante e Cancelamento</h2>
    <p>Após finalizar, o sistema gera o comprovante. Para cancelar, informe uma justificativa. O cancelamento reverte estoque, pagamentos, contas a receber, caixa e registra auditoria.</p>
    <img class="shot" src="screenshots/09-comprovante.png">
  </section>

  <section class="page">
    <h2>9. Estoque</h2>
    <p>O estoque reúne ingredientes, bebidas, adicionais e produtos de revenda. Toda movimentação registra produto, quantidade anterior, quantidade movimentada, quantidade final, tipo, usuário, data e motivo.</p>
    <p>Use ajustes para perdas, avarias, inventário, transferência e correções autorizadas.</p>
    <img class="shot" src="screenshots/10-estoque.png">
  </section>

  <section class="page">
    <h2>10. Compras</h2>
    <p>Ao registrar uma compra, os itens entram automaticamente no estoque. Compras parceladas podem gerar contas a pagar.</p>
    <ol>
      <li>Escolha fornecedor, data e documento.</li>
      <li>Informe produtos, quantidades e custos.</li>
      <li>Confirme a compra para atualizar o estoque.</li>
    </ol>
    <img class="shot" src="screenshots/11-compras.png">
  </section>

  <section class="page">
    <h2>11. Relatórios</h2>
    <p>Use filtros por período, tipo de pedido, mesa e usuário/garçom. É possível exportar CSV e imprimir em PDF pelo navegador.</p>
    <img class="shot" src="screenshots/12-relatorios.png">
  </section>

  <section class="page">
    <h2>12. Configurações</h2>
    <p>Configure dados da empresa, mensagem do comprovante, política de pedido sem estoque, estoque mínimo padrão, alerta de validade, moeda, impressão e backup automático.</p>
    <img class="shot" src="screenshots/13-configuracoes.png">
  </section>

  <section class="page">
    <h2>Boas práticas de operação</h2>
    <table>
      <tr><th>Rotina</th><th>Recomendação</th></tr>
      <tr><td>Início do dia</td><td>Abrir caixa e conferir estoque crítico.</td></tr>
      <tr><td>Durante atendimento</td><td>Registrar pedidos sempre pelo PDV e manter comandas identificadas.</td></tr>
      <tr><td>Compras</td><td>Lançar compras no mesmo dia para manter custo e estoque atualizados.</td></tr>
      <tr><td>Cancelamentos</td><td>Exigir justificativa real para manter auditoria confiável.</td></tr>
      <tr><td>Fim do dia</td><td>Fechar caixa, conferir diferenças e gerar relatório.</td></tr>
      <tr><td>Segurança</td><td>Trocar a senha inicial e criar usuários por perfil.</td></tr>
    </table>
  </section>
</body>
</html>`;

const htmlPath = path.join(docsDir, 'tutorial-restaurante-pro.html');
const pdfPath = path.join(docsDir, 'tutorial-restaurante-pro.pdf');
fs.writeFileSync(htmlPath, html);

const pdfPage = await browser.newPage({ viewport: { width: 1240, height: 1754 } });
await pdfPage.goto(`file://${htmlPath}`, { waitUntil: 'networkidle' });
await pdfPage.pdf({
  path: pdfPath,
  format: 'A4',
  printBackground: true,
  margin: { top: '12mm', right: '10mm', bottom: '12mm', left: '10mm' },
});

await browser.close();

console.log(`Tutorial HTML: ${htmlPath}`);
console.log(`Tutorial PDF: ${pdfPath}`);
