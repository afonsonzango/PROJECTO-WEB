const AlunoModel = require('../models/AlunoModel');

const MatriculaController = {
  form(req, res) {
    res.render('fazer_matricula', { erro: null, sucesso: null });
  },

  async submit(req, res) {
    try {
      const dados = {
        nome: req.body.nome,
        email: req.body.email,
        nif: req.body.nif,
        sexo: req.body.sexo,
        curso: req.body.curso,
        classe: req.body.classe,
        periodo: req.body.periodo,
        telefone: req.body.telefone,
        escola_anterior: req.body.escola,
        endereco: req.body.endereco || null,
        foto: req.files?.foto?.[0]?.filename || null,
        certificado: req.files?.certificado?.[0]?.filename || null,
      };

      const campos = ['nome', 'email', 'nif', 'sexo', 'curso', 'classe', 'periodo', 'telefone', 'escola_anterior'];
      for (const c of campos) {
        if (!dados[c]) return res.render('fazer_matricula', { erro: 'Preencha todos os campos obrigatórios.', sucesso: null });
      }

      await AlunoModel.criar(dados);
      res.render('fazer_matricula', { erro: null, sucesso: 'Matrícula enviada com sucesso! Aguarde a análise da secretaria.' });
    } catch (err) {
      const msg = err.code === '23505'
        ? 'Este email ou NIF já está cadastrado.'
        : 'Erro ao processar matrícula. Tente novamente.';
      res.render('fazer_matricula', { erro: msg, sucesso: null });
    }
  },

  statusForm(req, res) {
    res.render('status_matricula', { aluno: null, erro: null });
  },

  async status(req, res) {
    const { nome, email } = req.body;
    if (!nome || !email) return res.render('status_matricula', { aluno: null, erro: 'Preencha nome e email.' });
    const aluno = await AlunoModel.buscarPorEmailENome(email, nome);
    if (!aluno) return res.render('status_matricula', { aluno: null, erro: 'Nenhuma matrícula encontrada com esses dados.' });
    res.render('status_matricula', { aluno, erro: null });
  },
};

module.exports = MatriculaController;
