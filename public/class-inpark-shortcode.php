<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Inpark_Public_Shortcode {
    public function __construct(){
        add_shortcode('inpark_reserva_form', array($this, 'render'));
    }

    public function render($atts){
        $nonce = wp_create_nonce('inpark_reserva_nonce');
        
        // Obter complementos
        $complementos = get_option('inpark_complementos_items', array());
        if (!is_array($complementos)) {
            $complementos = array();
        }
        
        ob_start(); ?>
        <form id="inpark-reserva-form" class="inpark-form">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>"/>
            
            <!-- TÍTULO PRINCIPAL -->
            <h2>Dados da Reserva</h2>
            
            <!-- DADOS PESSOAIS -->
            <div class="form-section">
                <div class="form-row">
                    <label>Nome</label>
                    <input type="text" name="nome" placeholder="Digite seu nome" required>
                </div>
                
                <div class="form-row">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Digite seu email" required>
                </div>
                
                <div class="form-row">
                    <label>Telemóvel</label>
                    <input type="tel" name="telefone" placeholder="Digite seu telemóvel" required>
                </div>
            </div>
            
            <!-- ALUGUER À TUA MEDIDA -->
            <h3 class="section-title">Aluguer à tua medida</h3>
            
            <div class="form-section">
                <!-- Data e Horário -->
                <div class="form-row-group">
                    <div class="form-row">
                        <label>Data</label>
                        <input type="date" name="data" id="data" required>
                    </div>
                    
                    <div class="form-row">
                        <label>Horário</label>
                        <input type="text" name="horario" id="horario" placeholder="10:00-14:00" readonly>
                    </div>
                </div>
                
                <!-- Botões de Tipo de Aluguer -->
                <div class="aluguer-buttons">
                    <label class="btn-aluguer">
                        <input type="radio" name="tipo" value="exclusivo" id="tipo-exclusivo" required>
                        Aluguer Exclusivo
                    </label>
                    
                    <label class="btn-aluguer">
                        <input type="radio" name="tipo" value="parcial" id="tipo-parcial">
                        Aluguer Parcial
                    </label>
                </div>
                
                <!-- Preço Base (mostra ao lado do botão ativo) -->
                <div id="preco-base-display" style="text-align: right; color: #fbbf24; font-size: 20px; font-weight: 700; margin-top: -10px;"></div>
            </div>
            
            <!-- COMPLEMENTOS -->
            <h3 class="section-title">Complementos</h3>
            
            <div class="complementos-list">
                <?php if (!empty($complementos)): ?>
                    <?php foreach ($complementos as $comp): ?>
                        <label class="complemento-item">
                            <div class="complemento-left">
                                <input 
                                    type="checkbox" 
                                    name="complementos[]" 
                                    value="<?php echo esc_attr($comp['id']); ?>"
                                    data-preco="<?php echo esc_attr($comp['preco']); ?>"
                                    class="complemento-checkbox"
                                >
                                <span class="complemento-name"><?php echo esc_html($comp['nome']); ?></span>
                            </div>
                            <span class="complemento-price"><?php echo number_format($comp['preco'], 2, ',', '.'); ?>€</span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Complementos padrão se não houver configurados -->
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="laser_tag" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Laser Tag</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="workshop" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Workshop</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="dj" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">DJ</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="catering" data-preco="120" class="complemento-checkbox">
                            <span class="complemento-name">Catering</span>
                        </div>
                        <span class="complemento-price">120,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="pinturas" data-preco="50" class="complemento-checkbox">
                            <span class="complemento-name">Pinturas Faciais</span>
                        </div>
                        <span class="complemento-price">50,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="vinhos" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Prova De Vinhos</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="insuflavel" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Insuflável</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="piscina" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Piscina De Bolas</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                    
                    <label class="complemento-item">
                        <div class="complemento-left">
                            <input type="checkbox" name="complementos[]" value="beer" data-preco="0" class="complemento-checkbox">
                            <span class="complemento-name">Draft Your Beer</span>
                        </div>
                        <span class="complemento-price">0,00€</span>
                    </label>
                <?php endif; ?>
            </div>
            
            <!-- FATURAÇÃO (OPCIONAL) -->
            <div class="form-section" style="margin-top: 30px;">
                <label class="checkbox-label">
                    <input type="checkbox" id="toggle-fatura" name="has_fatura">
                    <span>Pretende Fatura com NIF?</span>
                </label>
                
                <div class="conditional-section hidden" id="fatura-fields">
                    <div class="form-row">
                        <label>Nome (Faturação)</label>
                        <input type="text" name="fatura_nome" id="fatura_nome">
                    </div>
                    <div class="form-row">
                        <label>Morada</label>
                        <input type="text" name="fatura_morada" id="fatura_morada">
                    </div>
                    <div class="form-row">
                        <label>NIF</label>
                        <input type="text" name="fatura_nif" id="fatura_nif" maxlength="9" pattern="[0-9]{9}">
                        <small style="color: rgba(255,255,255,0.7); display: block; margin-top: 5px;">9 dígitos</small>
                    </div>
                </div>
            </div>
            
            <!-- ORÇAMENTO -->
            <div class="orcamento-section">
                <div class="orcamento-label">Orçamento</div>
                <div class="orcamento-value" id="total-value">300,00€</div>
                <div class="orcamento-note">IVA incluído à taxa legal em vigor</div>
            </div>
            
            <!-- CAMPO HIDDEN PARA O TOTAL -->
            <input type="hidden" name="total" id="total-hidden" value="300">
            
            <!-- BOTÃO SUBMETER -->
            <button type="submit" class="btn-submit">Submeter</button>
            
            <!-- MENSAGEM -->
            <div id="form-msg"></div>
        </form>
        <?php
        return ob_get_clean();
    }
}

new Inpark_Public_Shortcode();
