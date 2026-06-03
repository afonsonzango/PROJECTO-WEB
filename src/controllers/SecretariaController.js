const SecretariaModel = require('../models/SecretariaModel');
const AlunoModel = require('../models/AlunoModel');

const SecretariaController = {
  loginForm(req, res) {
    res.render('login_secretaria', { erro: null });
  },

  async login(req, res) {
    const { usuario, senha } = req.body;
    if (!usuario || !senha) return res.render('login_secretaria', { erro: 'Preencha todos os campos.' });
    if (senha.length < 6) return res.render('login_secretaria', { erro: 'Senha deve ter pelo menos 6 caracteres.' });
    const resultado = await SecretariaModel.criarOuAutenticar(usuario, senha);
    if (!resultado) return res.render('login_secretaria', { erro: 'Credenciais inválidas.' });
    req.session.secretaria = { usuario: resultado.usuario };
    res.redirect('/secretaria/painel');
  },

  async painel(req, res) {
    const busca = req.query.buscar?.trim() || null;
    const filtro = req.query.filter || null;
    const [alunos, stats, reconfirmacoes] = await Promise.all([
      AlunoModel.listarTodos(filtro, busca),
      AlunoModel.contagens(),
      AlunoModel.reconfirmacoesPendentes(),
    ]);
    const secao = busca || filtro ? 'alunos' : 'dashboard';
    res.render('painel_secretaria', {
      alunos,
      stats,
      reconfirmacoes,
      busca: busca || '',
      filtro: filtro || 'all',
      secao,
      usuario: req.session.secretaria.usuario,
      flash: req.session.flash || null,
    });
    delete req.session.flash;
  },

  async aprovar(req, res) {
    const id = parseInt(req.params.id);
    const idOficial = await AlunoModel.aprovar(id);
    req.session.flash = { tipo: 'sucesso', msg: `Matrícula aprovada! ID: ${idOficial}` };
    res.redirect('/secretaria/painel');
  },

  async recusar(req, res) {
    const id = parseInt(req.params.id);
    const mensagem = req.body.mensagem?.trim();
    if (!mensagem) {
      req.session.flash = { tipo: 'erro', msg: 'Informe o motivo da recusa.' };
      return res.redirect('/secretaria/painel');
    }
    await AlunoModel.recusar(id, mensagem);
    req.session.flash = { tipo: 'sucesso', msg: 'Matrícula recusada.' };
    res.redirect('/secretaria/painel');
  },

  async aceitarReconf(req, res) {
    await AlunoModel.aceitarReconfirmacao(parseInt(req.params.id));
    req.session.flash = { tipo: 'sucesso', msg: 'Reconfirmação aceita.' };
    res.redirect('/secretaria/painel');
  },

  async recusarReconf(req, res) {
    await AlunoModel.recusarReconfirmacao(parseInt(req.params.id));
    req.session.flash = { tipo: 'sucesso', msg: 'Reconfirmação recusada.' };
    res.redirect('/secretaria/painel');
  },

  async alterarSenha(req, res) {
    const { senha_atual, senha_nova } = req.body;
    const resultado = await SecretariaModel.alterarSenha(req.session.secretaria.usuario, senha_atual, senha_nova);
    req.session.flash = resultado.ok
      ? { tipo: 'sucesso', msg: 'Senha alterada com sucesso.' }
      : { tipo: 'erro', msg: resultado.erro };
    res.redirect('/secretaria/painel');
  },

  logout(req, res) {
    req.session.destroy();
    res.redirect('/login/secretaria');
  },
};

module.exports = SecretariaController;
