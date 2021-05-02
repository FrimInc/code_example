using System;
using System.Collections;
using System.Collections.Generic;
using System.Net.Configuration;
using System.Runtime.CompilerServices;

using UnityEngine;
using UnityEngine.UI;
using Random = System.Random;

public class Mob : MonoBehaviour
{

    public bool good = false;
    public bool evil = false;

    public bool followPlayer = false;
    private Vector3 posTo;

    private Rigidbody rbody;
    private GameObject player ;
    public GameObject grave ;
    
    public float posChangeFriquency = 5f;
    private float nextChangePostFriquncy;

    public RectTransform imgCanvas;
    private RectTransform img;
    private void Start()
    {
        player = GameObject.FindGameObjectWithTag("Player");
        rbody = GetComponent<Rigidbody>();
        posTo = transform.position;
        
        if (imgCanvas == null)
        {
            return;
        }
        
        img = Instantiate(imgCanvas);
      
        img.transform.SetParent(GameObject.FindGameObjectWithTag("Canvas").transform);
        img.gameObject.GetComponent<wordfollover>().shift=new Vector3(-0.35f,0.35f,1.5f);
        img.gameObject.GetComponent<wordfollover>().MakeStart(gameObject);
        //   nextChangePostFriquncy = Time.timeSinceLevelLoad + posChangeFriquency;
        //    nextChangeForce = Time.timeSinceLevelLoad + changeForcePeriod;
    }

 

    public float forceToRandom = 30f;
    public float forceToPlayer = 1f;
    
    public float changeForcePeriod = 5f;

    private float nextChangeForce = 0f;

    public bool stackable = false;
    private void OnTriggerEnter(Collider other)
    {
        if (!stackable)
        {
            return;
        }
        if (other.GetComponent<Mob>())
        {
            if (other.GetComponent<Mob>().evil)
            {
                if (other.transform.localScale.x <= transform.localScale.x)
                {
                    if (other.GetComponent<Mob>().img)
                    {
                        other.GetComponent<Mob>().stackable = false;
                        if (transform.localScale.x < 5f)
                        {
                            transform.localScale += other.transform.localScale/10;    
                            if (img)
                            {
                                img.transform.localScale += other.GetComponent<Mob>().img.localScale/10;    
                            }
                        
                        }
                        weapChance += other.GetComponent<Mob>().weapChance;
                    
                        GetComponent<KillabaleScript>().health += other.GetComponent<KillabaleScript>().health*0.2f;
                        other.GetComponent<Mob>().makeDissapear();
                        if (transform.localScale.x > 2f)
                        {
                         
                            img.transform.GetChild(0).gameObject.SetActive(true);
                        }
                        if (transform.localScale.x > 4f)
                        {
                            stackable = false;
                            img.transform.GetChild(0).gameObject.SetActive(true);
                        }
                    }
                }
                
               
            }
            
           
        }
        
       
        
    }
    
    
    public void FixedUpdate()
    {
        if (dissapearing)
        {

            if (transform.localScale.x < 0.02)
            {
                if (img && img.gameObject)
                {
                    Destroy(img.gameObject);    
                }
        
                Destroy(gameObject);
                return;
            }

            Vector3 dispr = transform.localScale;
            dispr *= 0.95f;
            transform.localScale = dispr;
            
            dispr = img.transform.localScale;
            dispr *= 0.95f;
            img.transform.localScale = dispr;
            
            return;
        }
        
      //  transform.forward = Vector3.Lerp(transform.forward, rbody.velocity.normalized, 10f*Time.deltaTime);
        transform.up = Vector3.up;
        
        Vector3 force = Vector3.zero;
        
        
        if (followPlayer && nextChangeForce < Time.timeSinceLevelLoad)
        {
            nextChangeForce = Time.timeSinceLevelLoad + changeForcePeriod;
            if (forceToRandom > 1f)
            {
                forceToRandom--;
                forceToPlayer++;
            }
        }
        
        force+=(posTo-transform.position).normalized*forceToRandom*Time.deltaTime*transform.localScale.x;
        if (followPlayer)
        {
            force += (player.transform.position - transform.position).normalized * forceToPlayer *
                     Time.deltaTime * Convert.ToSingle(Math.Pow(Convert.ToDouble(transform.localScale.x), 1.1));
        }

        force += Camera.main.GetComponent<Tools>().getRandomVector(false, 5f) * 5f * Time.deltaTime *
                 transform.localScale.x;
        
        if (nextChangePostFriquncy<Time.timeSinceLevelLoad)
        {
            posTo = transform.position + Camera.main.GetComponent<Tools>().getRandomVector(false, 20f*transform.localScale.x);
            nextChangePostFriquncy = Time.timeSinceLevelLoad + posChangeFriquency;
        }

        force+=Vector3.up;
        
        GetComponent<Rigidbody>().AddForce(force,ForceMode.Impulse);
        
    }

    public bool dissapearing = false;

    public GameObject weapBonus;
    public int weapChance = 10;
    
    public int bonusChance = 100;

    
    public static bool firstBonus = true;
    static  Random random = new Random();
    private int randomNumber;
    public void makeDissapear(bool withBonus = false)
    {
        if (!dissapearing)
        {
            
            dissapearing = true;
            if (grave)
            {
                Instantiate(grave, transform.position, Quaternion.identity);    
            }
            
            if (evil && withBonus)
            {
                
                if (weapBonus)
                {
                    if (firstBonus)
                    {
                        GameObject wepB = Instantiate(weapBonus, transform.position, Quaternion.identity);
                        firstBonus = false;
                        wepB.GetComponent<Rigidbody>().AddForce(Camera.main.GetComponent<Tools>().getRandomVector(false,1f).normalized*15f,ForceMode.Impulse);
                        return;
                    }
                    else
                    {

                        randomNumber = random.Next(0, 100);
                        if (randomNumber <= weapChance)
                        {
                            GameObject wepB = Instantiate(weapBonus, transform.position, Quaternion.identity);
                            wepB.GetComponent<Rigidbody>().AddForce(Camera.main.GetComponent<Tools>().getRandomVector(false,1f).normalized*15f,ForceMode.Impulse);
                            return;
                        }
                    }
                }

               
                randomNumber = random.Next(0, 100);
                if (randomNumber <= bonusChance)
                {
                    GameObject.FindGameObjectWithTag("BonusVariants").GetComponent<BonusFactory>()
                        .makeBonus(transform.position);
                }    
               
                
            }


          
        }
        
    }
    
}